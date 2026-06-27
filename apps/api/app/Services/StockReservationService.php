<?php

namespace App\Services;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\DistributionItem;
use App\Models\InventoryItem;
use App\Models\StockLot;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockReservationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function stockCheck(AidBatch $batch): array
    {
        return $batch->distributions()
            ->with('items.inventoryItem')
            ->get()
            ->flatMap(fn (AidDistribution $distribution): Collection => $distribution->items)
            ->filter(fn (DistributionItem $item): bool => $item->inventory_item_id !== null && (float) $item->quantity > 0)
            ->groupBy('inventory_item_id')
            ->map(function (Collection $items, int $inventoryItemId) use ($batch): array {
                $firstItem = $items->first();
                $required = (float) $items->sum(fn (DistributionItem $item): float => (float) $item->quantity);
                $available = (float) StockLot::query()
                    ->where('organization_id', $batch->organization_id)
                    ->where('inventory_item_id', $inventoryItemId)
                    ->when($batch->warehouse_id, fn (Builder $query): Builder => $query->where('warehouse_id', $batch->warehouse_id))
                    ->sum('remaining_quantity');

                return [
                    'inventory_item_id' => $inventoryItemId,
                    'sku' => $firstItem?->inventoryItem?->sku,
                    'name' => $firstItem?->inventoryItem?->name,
                    'unit' => $firstItem?->inventoryItem?->unit,
                    'required_quantity' => number_format($required, 3, '.', ''),
                    'available_quantity' => number_format($available, 3, '.', ''),
                    'sufficient' => $available >= $required,
                ];
            })
            ->values()
            ->all();
    }

    public function reserveBatch(AidBatch $batch, User $user): void
    {
        abort_if($batch->reservations()->where('status', 'reserved')->exists(), 422, 'This batch already has active stock reservations.');

        $batch->loadMissing('distributions.items');

        foreach ($batch->distributions as $distribution) {
            foreach ($distribution->items as $item) {
                if ($item->inventory_item_id === null || (float) $item->quantity <= 0) {
                    continue;
                }

                $this->reserveItem($batch, $distribution, $item, $user);
            }
        }
    }

    public function releaseBatch(AidBatch $batch, User $user, ?AidDistribution $onlyDistribution = null): void
    {
        $reservations = StockReservation::query()
            ->where('organization_id', $batch->organization_id)
            ->where('aid_batch_id', $batch->id)
            ->where('status', 'reserved')
            ->when($onlyDistribution, fn (Builder $query): Builder => $query->where('aid_distribution_id', $onlyDistribution->id))
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $lot = StockLot::query()
                ->whereKey($reservation->stock_lot_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lot->increment('remaining_quantity', $reservation->quantity);
            $lot->decrement('reserved_quantity', $reservation->quantity);
            $reservation->update(['status' => 'released']);

            $this->recordMovement($reservation, $lot->fresh(), 'released', $user, 'Released reserved stock.');
        }
    }

    public function distribute(AidDistribution $distribution, User $user): void
    {
        $reservations = StockReservation::query()
            ->where('organization_id', $distribution->organization_id)
            ->where('aid_distribution_id', $distribution->id)
            ->where('status', 'reserved')
            ->lockForUpdate()
            ->get();

        abort_if(
            $distribution->items()->whereNotNull('inventory_item_id')->exists() && $reservations->isEmpty(),
            422,
            'This distribution has inventory items but no reserved stock.',
        );

        foreach ($reservations as $reservation) {
            $lot = StockLot::query()
                ->whereKey($reservation->stock_lot_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((float) $lot->reserved_quantity >= (float) $reservation->quantity, 422, 'Reserved stock is no longer available.');

            $lot->decrement('reserved_quantity', $reservation->quantity);
            $reservation->update(['status' => 'distributed']);

            $this->recordMovement($reservation, $lot->fresh(), 'distributed', $user, 'Distributed reserved stock.');
        }
    }

    private function reserveItem(AidBatch $batch, AidDistribution $distribution, DistributionItem $item, User $user): void
    {
        $remaining = (float) $item->quantity;

        $lots = $this->lotQuery($batch, $item)
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $lot->remaining_quantity;

            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);
            $lot->decrement('remaining_quantity', $take);
            $lot->increment('reserved_quantity', $take);

            $reservation = StockReservation::create([
                'organization_id' => $batch->organization_id,
                'aid_batch_id' => $batch->id,
                'aid_distribution_id' => $distribution->id,
                'distribution_item_id' => $item->id,
                'stock_lot_id' => $lot->id,
                'quantity' => $take,
                'status' => 'reserved',
            ]);

            $this->recordMovement($reservation, $lot->fresh(), 'reserved', $user, 'Reserved stock for aid batch.');
            $remaining -= $take;
        }

        $inventoryItem = InventoryItem::find($item->inventory_item_id);
        abort_if($remaining > 0, 422, 'Not enough available stock for '.($inventoryItem?->sku ?? 'inventory item').'.');
    }

    private function lotQuery(AidBatch $batch, DistributionItem $item): Builder
    {
        return StockLot::query()
            ->where('organization_id', $batch->organization_id)
            ->where('inventory_item_id', $item->inventory_item_id)
            ->when($batch->warehouse_id, fn (Builder $query): Builder => $query->where('warehouse_id', $batch->warehouse_id))
            ->when($item->stock_lot_id, fn (Builder $query): Builder => $query->whereKey($item->stock_lot_id))
            ->where('remaining_quantity', '>', 0)
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->orderBy('received_at');
    }

    private function recordMovement(StockReservation $reservation, StockLot $lot, string $movementType, User $user, string $notes): void
    {
        StockMovement::create([
            'organization_id' => $reservation->organization_id,
            'warehouse_id' => $lot->warehouse_id,
            'inventory_item_id' => $lot->inventory_item_id,
            'stock_lot_id' => $lot->id,
            'movement_type' => $movementType,
            'quantity' => $reservation->quantity,
            'reference_type' => 'stock_reservation',
            'reference_id' => $reservation->id,
            'notes' => $notes,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
    }
}
