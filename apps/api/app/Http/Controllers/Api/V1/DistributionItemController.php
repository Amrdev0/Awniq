<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DistributionItemRequest;
use App\Http\Resources\DistributionItemResource;
use App\Models\AidDistribution;
use App\Models\DistributionItem;
use App\Models\StockLot;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DistributionItemController extends Controller
{
    public function index(AidDistribution $distribution): AnonymousResourceCollection
    {
        $this->assertDistributionScope($distribution);

        $items = $distribution->items()
            ->with(['inventoryItem', 'stockLot', 'reservations.stockLot'])
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return DistributionItemResource::collection($items);
    }

    public function store(DistributionItemRequest $request, AidDistribution $distribution, AuditLogService $auditLogService): DistributionItemResource
    {
        $this->assertDistributionScope($distribution);
        $this->assertDistributionMutable($distribution);
        $this->assertStockLotMatches($request, $distribution);

        $item = $distribution->items()->create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization_id,
        ]);

        $auditLogService->record('distribution_item.created', $item, null, $item->toArray(), $request);

        return new DistributionItemResource($item->load(['inventoryItem', 'stockLot', 'reservations']));
    }

    public function update(DistributionItemRequest $request, AidDistribution $distribution, DistributionItem $item, AuditLogService $auditLogService): DistributionItemResource
    {
        $this->assertItemScope($distribution, $item);
        $this->assertDistributionMutable($distribution);
        $this->assertStockLotMatches($request, $distribution);

        $oldValues = $item->toArray();
        $item->update($request->validated());

        $auditLogService->record('distribution_item.updated', $item, $oldValues, $item->fresh()->toArray(), $request);

        return new DistributionItemResource($item->fresh()->load(['inventoryItem', 'stockLot', 'reservations']));
    }

    public function destroy(AidDistribution $distribution, DistributionItem $item, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertItemScope($distribution, $item);
        $this->assertDistributionMutable($distribution);
        abort_if($item->reservations()->exists(), 422, 'Distribution items with stock reservations cannot be deleted.');

        $oldValues = $item->toArray();
        $item->delete();

        $auditLogService->record('distribution_item.deleted', $item, $oldValues, null, request());

        return ApiResponse::success(message: 'Distribution item deleted successfully.');
    }

    private function assertDistributionScope(AidDistribution $distribution): void
    {
        abort_unless($distribution->organization_id === request()->user()->organization_id, 404);
    }

    private function assertItemScope(AidDistribution $distribution, DistributionItem $item): void
    {
        $this->assertDistributionScope($distribution);
        abort_unless($item->organization_id === request()->user()->organization_id, 404);
        abort_unless($item->aid_distribution_id === $distribution->id, 404);
    }

    private function assertDistributionMutable(AidDistribution $distribution): void
    {
        $distribution->loadMissing('aidBatch');
        abort_unless(in_array($distribution->aidBatch->status, ['draft', 'pending_approval'], true), 422, 'This distribution can no longer be edited.');
        abort_unless(in_array($distribution->status, ['draft', 'pending_approval'], true), 422, 'This distribution can no longer be edited.');
    }

    private function assertStockLotMatches(DistributionItemRequest $request, AidDistribution $distribution): void
    {
        if (! $request->filled('stock_lot_id')) {
            return;
        }

        $distribution->loadMissing('aidBatch');
        $lot = StockLot::where('organization_id', $request->user()->organization_id)->findOrFail($request->integer('stock_lot_id'));

        abort_unless($lot->inventory_item_id === $request->integer('inventory_item_id'), 422, 'Stock lot must match the selected inventory item.');

        if ($distribution->aidBatch->warehouse_id) {
            abort_unless($lot->warehouse_id === $distribution->aidBatch->warehouse_id, 422, 'Stock lot must belong to the batch warehouse.');
        }
    }
}
