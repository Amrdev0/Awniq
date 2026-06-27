<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\ReceiveStockRequest;
use App\Http\Resources\StockLotResource;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\AuditLogService;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $movements = StockMovement::query()
            ->with(['warehouse', 'inventoryItem', 'stockLot', 'creator'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('warehouse_id'), fn ($query, string $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->query('inventory_item_id'), fn ($query, string $itemId) => $query->where('inventory_item_id', $itemId))
            ->when($request->query('stock_lot_id'), fn ($query, string $lotId) => $query->where('stock_lot_id', $lotId))
            ->when($request->query('movement_type'), fn ($query, string $movementType) => $query->where('movement_type', $movementType))
            ->when($request->query('date_from'), fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    public function receive(ReceiveStockRequest $request, StockMovementService $stockMovementService, AuditLogService $auditLogService): StockLotResource
    {
        $lot = $stockMovementService->receive($request->validated(), $request->user());

        $auditLogService->record('stock.received', $lot, null, $lot->toArray(), $request);

        return new StockLotResource($lot->load(['warehouse', 'inventoryItem']));
    }

    public function adjust(AdjustStockRequest $request, StockMovementService $stockMovementService, AuditLogService $auditLogService): StockMovementResource
    {
        $movement = $stockMovementService->adjust($request->validated(), $request->user());

        $auditLogService->record('stock.adjusted', $movement, null, $movement->toArray(), $request);

        return new StockMovementResource($movement->load(['warehouse', 'inventoryItem', 'stockLot', 'creator']));
    }

    public function show(StockMovement $stockMovement): StockMovementResource
    {
        $this->assertMovementScope($stockMovement);

        return new StockMovementResource($stockMovement->load(['warehouse', 'inventoryItem', 'stockLot', 'creator']));
    }

    private function assertMovementScope(StockMovement $stockMovement): void
    {
        abort_unless($stockMovement->organization_id === request()->user()->organization_id, 404);
    }
}
