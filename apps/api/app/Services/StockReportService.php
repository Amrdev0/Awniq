<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockLot;
use Illuminate\Support\Collection;

class StockReportService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function summary(int $organizationId, ?int $warehouseId = null): array
    {
        return InventoryItem::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get()
            ->map(function (InventoryItem $item) use ($warehouseId): array {
                $lotQuery = $item->stockLots();

                if ($warehouseId) {
                    $lotQuery->where('warehouse_id', $warehouseId);
                }

                $available = (float) (clone $lotQuery)->sum('remaining_quantity');
                $reserved = (float) (clone $lotQuery)->sum('reserved_quantity');

                return [
                    'inventory_item_id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'category' => $item->category,
                    'unit' => $item->unit,
                    'minimum_stock_level' => number_format((float) $item->minimum_stock_level, 3, '.', ''),
                    'available_quantity' => number_format($available, 3, '.', ''),
                    'reserved_quantity' => number_format($reserved, 3, '.', ''),
                    'low_stock' => $available <= (float) $item->minimum_stock_level,
                    'track_expiry' => $item->track_expiry,
                    'status' => $item->status,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lowStock(int $organizationId): array
    {
        return collect($this->summary($organizationId))
            ->filter(fn (array $row): bool => (bool) $row['low_stock'])
            ->values()
            ->all();
    }

    public function expiring(int $organizationId, int $days = 30): Collection
    {
        return StockLot::query()
            ->with(['warehouse', 'inventoryItem'])
            ->where('organization_id', $organizationId)
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_date')
            ->get();
    }
}
