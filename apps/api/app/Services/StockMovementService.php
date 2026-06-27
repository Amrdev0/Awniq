<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockLot;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function receive(array $payload, User $user): StockLot
    {
        return DB::transaction(function () use ($payload, $user): StockLot {
            $warehouse = Warehouse::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($payload['warehouse_id'])
                ->firstOrFail();

            $item = InventoryItem::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($payload['inventory_item_id'])
                ->firstOrFail();

            abort_unless($warehouse->status === 'active', 422, 'Stock can only be received into active warehouses.');
            abort_unless($item->status === 'active', 422, 'Inactive inventory items cannot receive new stock.');
            $this->assertExpiryRules($item, $payload['received_at'], $payload['expiry_date'] ?? null);

            $lot = StockLot::create([
                'organization_id' => $user->organization_id,
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $item->id,
                'source_type' => $payload['source_type'],
                'source_id' => $payload['source_id'] ?? null,
                'quantity' => $payload['quantity'],
                'remaining_quantity' => $payload['quantity'],
                'reserved_quantity' => 0,
                'expiry_date' => $payload['expiry_date'] ?? null,
                'received_at' => $payload['received_at'],
            ]);

            $this->recordMovement($lot, 'stock_in', $payload['quantity'], $payload['notes'] ?? null, $user, 'stock_lot', $lot->id);

            return $lot->load(['warehouse', 'inventoryItem']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function adjust(array $payload, User $user): StockMovement
    {
        return DB::transaction(function () use ($payload, $user): StockMovement {
            $warehouse = Warehouse::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($payload['warehouse_id'])
                ->firstOrFail();

            $item = InventoryItem::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($payload['inventory_item_id'])
                ->firstOrFail();

            abort_unless($warehouse->status === 'active', 422, 'Adjustments require an active warehouse.');

            $movementType = $payload['movement_type'];

            if ($movementType === 'adjustment_in') {
                $lot = $this->resolveAdjustmentInLot($payload, $warehouse, $item, $user);
                $lot->increment('remaining_quantity', $payload['quantity']);
                $lot->increment('quantity', $payload['quantity']);

                return $this->recordMovement($lot->fresh(), 'adjustment_in', $payload['quantity'], $payload['notes'], $user, 'stock_lot', $lot->id)
                    ->load(['warehouse', 'inventoryItem', 'stockLot', 'creator']);
            }

            abort_unless(! empty($payload['stock_lot_id']), 422, 'A stock lot is required for stock-out adjustments.');

            $lot = StockLot::query()
                ->where('organization_id', $user->organization_id)
                ->where('warehouse_id', $warehouse->id)
                ->where('inventory_item_id', $item->id)
                ->whereKey($payload['stock_lot_id'] ?? 0)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((float) $lot->remaining_quantity >= (float) $payload['quantity'], 422, 'Adjustment cannot make available stock negative.');

            $lot->decrement('remaining_quantity', $payload['quantity']);

            return $this->recordMovement($lot->fresh(), $movementType, $payload['quantity'], $payload['notes'], $user, 'stock_lot', $lot->id)
                ->load(['warehouse', 'inventoryItem', 'stockLot', 'creator']);
        });
    }

    private function resolveAdjustmentInLot(array $payload, Warehouse $warehouse, InventoryItem $item, User $user): StockLot
    {
        if (! empty($payload['stock_lot_id'])) {
            return StockLot::query()
                ->where('organization_id', $user->organization_id)
                ->where('warehouse_id', $warehouse->id)
                ->where('inventory_item_id', $item->id)
                ->whereKey($payload['stock_lot_id'])
                ->lockForUpdate()
                ->firstOrFail();
        }

        $this->assertExpiryRules($item, now()->toDateString(), $payload['expiry_date'] ?? null);

        return StockLot::create([
            'organization_id' => $user->organization_id,
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'source_type' => 'adjustment',
            'quantity' => 0,
            'remaining_quantity' => 0,
            'reserved_quantity' => 0,
            'expiry_date' => $payload['expiry_date'] ?? null,
            'received_at' => now(),
        ]);
    }

    private function recordMovement(
        StockLot $lot,
        string $movementType,
        mixed $quantity,
        ?string $notes,
        User $user,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return StockMovement::create([
            'organization_id' => $lot->organization_id,
            'warehouse_id' => $lot->warehouse_id,
            'inventory_item_id' => $lot->inventory_item_id,
            'stock_lot_id' => $lot->id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    private function assertExpiryRules(InventoryItem $item, string $receivedAt, ?string $expiryDate): void
    {
        abort_if($item->track_expiry && ! $expiryDate, 422, 'Expiry date is required for this inventory item.');
        abort_if(
            $expiryDate && Carbon::parse($expiryDate)->lt(Carbon::parse($receivedAt)->startOfDay()),
            422,
            'Expiry date cannot be before the received date.',
        );
    }
}
