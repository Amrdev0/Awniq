<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\StockMovementResource;
use App\Models\InventoryItem;
use App\Services\AuditLogService;
use App\Services\StockReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = InventoryItem::query()
            ->withCount(['stockLots', 'stockMovements'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('category'), fn ($query, string $category) => $query->where('category', $category))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->has('track_expiry'), fn ($query) => $query->where('track_expiry', $request->boolean('track_expiry')))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return InventoryItemResource::collection($items);
    }

    public function store(InventoryItemRequest $request, AuditLogService $auditLogService): InventoryItemResource
    {
        $validated = $request->validated();
        $item = InventoryItem::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'minimum_stock_level' => $validated['minimum_stock_level'] ?? 0,
            'track_expiry' => $validated['track_expiry'] ?? false,
            'status' => $validated['status'] ?? 'active',
        ]);

        $auditLogService->record('inventory_item.created', $item, null, $item->toArray(), $request);

        return new InventoryItemResource($item->loadCount(['stockLots', 'stockMovements']));
    }

    public function show(InventoryItem $inventoryItem): InventoryItemResource
    {
        $this->assertItemScope($inventoryItem);

        return new InventoryItemResource($inventoryItem->load(['stockLots.warehouse'])->loadCount(['stockLots', 'stockMovements']));
    }

    public function update(InventoryItemRequest $request, InventoryItem $inventoryItem, AuditLogService $auditLogService): InventoryItemResource
    {
        $this->assertItemScope($inventoryItem);

        $oldValues = $inventoryItem->toArray();
        $inventoryItem->update($request->validated());

        $auditLogService->record('inventory_item.updated', $inventoryItem, $oldValues, $inventoryItem->fresh()->toArray(), $request);

        return new InventoryItemResource($inventoryItem->fresh()->loadCount(['stockLots', 'stockMovements']));
    }

    public function destroy(InventoryItem $inventoryItem, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertItemScope($inventoryItem);
        abort_if($inventoryItem->stockLots()->exists() || $inventoryItem->stockMovements()->exists(), 422, 'Inventory items with stock history cannot be deleted.');

        $oldValues = $inventoryItem->toArray();
        $inventoryItem->delete();

        $auditLogService->record('inventory_item.deleted', $inventoryItem, $oldValues, null, request());

        return ApiResponse::success(message: 'Inventory item deleted successfully.');
    }

    public function stock(InventoryItem $inventoryItem, Request $request, StockReportService $stockReportService): JsonResponse
    {
        $this->assertItemScope($inventoryItem);

        $summary = collect($stockReportService->summary($request->user()->organization_id, $request->integer('warehouse_id') ?: null))
            ->firstWhere('inventory_item_id', $inventoryItem->id);

        return response()->json(['data' => $summary]);
    }

    public function movements(InventoryItem $inventoryItem, Request $request): AnonymousResourceCollection
    {
        $this->assertItemScope($inventoryItem);

        $movements = $inventoryItem->stockMovements()
            ->with(['warehouse', 'stockLot', 'creator'])
            ->when($request->query('warehouse_id'), fn ($query, string $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->query('movement_type'), fn ($query, string $movementType) => $query->where('movement_type', $movementType))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    private function assertItemScope(InventoryItem $inventoryItem): void
    {
        abort_unless($inventoryItem->organization_id === request()->user()->organization_id, 404);
    }
}
