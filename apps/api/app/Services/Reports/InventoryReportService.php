<?php

namespace App\Services\Reports;

use App\Models\StockMovement;
use App\Services\StockReportService;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public function __construct(private readonly StockReportService $stockReportService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $warehouseId = isset($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : null;

        return [
            'stock_summary' => $this->stockReportService->summary($organizationId, $warehouseId),
            'low_stock' => $this->stockReportService->lowStock($organizationId),
            'expiring_stock' => $this->stockReportService->expiring($organizationId, 30)
                ->map(fn ($lot): array => [
                    'stock_lot_id' => $lot->id,
                    'sku' => $lot->inventoryItem?->sku,
                    'name' => $lot->inventoryItem?->name,
                    'warehouse' => $lot->warehouse?->code,
                    'remaining_quantity' => $lot->remaining_quantity,
                    'expiry_date' => $lot->expiry_date?->toDateString(),
                ])
                ->all(),
            'movement_summary' => StockMovement::query()
                ->where('organization_id', $organizationId)
                ->when($filters['category'] ?? null, fn ($query, string $category) => $query->whereHas('inventoryItem', fn ($query) => $query->where('category', $category)))
                ->select('movement_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as quantity'))
                ->groupBy('movement_type')
                ->orderBy('movement_type')
                ->get()
                ->map(fn ($row): array => [
                    'movement_type' => $row->movement_type,
                    'count' => (int) $row->count,
                    'quantity' => number_format((float) $row->quantity, 3, '.', ''),
                ])
                ->all(),
        ];
    }
}
