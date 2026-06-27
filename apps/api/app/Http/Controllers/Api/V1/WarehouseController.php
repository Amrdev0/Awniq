<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WarehouseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $warehouses = Warehouse::query()
            ->with(['branch', 'manager'])
            ->withCount(['stockLots', 'stockMovements'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('branch_id'), fn ($query, string $branchId) => $query->where('branch_id', $branchId))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return WarehouseResource::collection($warehouses);
    }

    public function store(WarehouseRequest $request, AuditLogService $auditLogService): WarehouseResource
    {
        $validated = $request->validated();
        $warehouse = Warehouse::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'status' => $validated['status'] ?? 'active',
        ]);

        $auditLogService->record('warehouse.created', $warehouse, null, $warehouse->toArray(), $request);

        return new WarehouseResource($warehouse->load(['branch', 'manager'])->loadCount(['stockLots', 'stockMovements']));
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        $this->assertWarehouseScope($warehouse);

        return new WarehouseResource($warehouse->load(['branch', 'manager'])->loadCount(['stockLots', 'stockMovements']));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse, AuditLogService $auditLogService): WarehouseResource
    {
        $this->assertWarehouseScope($warehouse);

        $oldValues = $warehouse->toArray();
        $warehouse->update($request->validated());

        $auditLogService->record('warehouse.updated', $warehouse, $oldValues, $warehouse->fresh()->toArray(), $request);

        return new WarehouseResource($warehouse->fresh()->load(['branch', 'manager'])->loadCount(['stockLots', 'stockMovements']));
    }

    public function destroy(Warehouse $warehouse, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertWarehouseScope($warehouse);
        abort_if($warehouse->stockLots()->exists() || $warehouse->stockMovements()->exists(), 422, 'Warehouses with stock history cannot be deleted.');

        $oldValues = $warehouse->toArray();
        $warehouse->delete();

        $auditLogService->record('warehouse.deleted', $warehouse, $oldValues, null, request());

        return ApiResponse::success(message: 'Warehouse deleted successfully.');
    }

    private function assertWarehouseScope(Warehouse $warehouse): void
    {
        abort_unless($warehouse->organization_id === request()->user()->organization_id, 404);
    }
}
