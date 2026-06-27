<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveStockRequest;
use App\Http\Resources\StockLotResource;
use App\Models\StockLot;
use App\Services\AuditLogService;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockLotController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $lots = StockLot::query()
            ->with(['warehouse', 'inventoryItem'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('warehouse_id'), fn ($query, string $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->query('inventory_item_id'), fn ($query, string $itemId) => $query->where('inventory_item_id', $itemId))
            ->when($request->query('source_type'), fn ($query, string $sourceType) => $query->where('source_type', $sourceType))
            ->when($request->boolean('available_only'), fn ($query) => $query->where('remaining_quantity', '>', 0))
            ->when($request->query('expiring_before'), fn ($query, string $date) => $query->whereDate('expiry_date', '<=', $date))
            ->orderByDesc('received_at')
            ->paginate($request->integer('per_page', 15));

        return StockLotResource::collection($lots);
    }

    public function store(ReceiveStockRequest $request, StockMovementService $stockMovementService, AuditLogService $auditLogService): StockLotResource
    {
        $lot = $stockMovementService->receive($request->validated(), $request->user());

        $auditLogService->record('stock_lot.received', $lot, null, $lot->toArray(), $request);

        return new StockLotResource($lot->load(['warehouse', 'inventoryItem']));
    }

    public function show(StockLot $stockLot): StockLotResource
    {
        $this->assertLotScope($stockLot);

        return new StockLotResource($stockLot->load(['warehouse', 'inventoryItem', 'movements.creator']));
    }

    private function assertLotScope(StockLot $stockLot): void
    {
        abort_unless($stockLot->organization_id === request()->user()->organization_id, 404);
    }
}
