<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(Role::query()->with('permissions')->orderBy('name')->paginate(request()->integer('per_page', 50)));
    }

    public function store(RoleRequest $request, AuditLogService $auditLogService): RoleResource
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
            'is_protected' => false,
        ]);

        $role->syncPermissions($request->validated('permissions') ?? []);
        $auditLogService->record('role.created', $role, null, $role->load('permissions')->toArray(), $request);

        return new RoleResource($role->load('permissions'));
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(RoleRequest $request, Role $role, AuditLogService $auditLogService): RoleResource
    {
        $oldValues = $role->load('permissions')->toArray();

        if (! $role->is_protected) {
            $role->update(['name' => $request->validated('name')]);
        }

        $role->syncPermissions($request->validated('permissions') ?? []);
        $auditLogService->record('role.updated', $role, $oldValues, $role->fresh()->load('permissions')->toArray(), $request);

        return new RoleResource($role->fresh()->load('permissions'));
    }

    public function destroy(Role $role, AuditLogService $auditLogService): JsonResponse
    {
        abort_if((bool) $role->is_protected, 422, 'Protected roles cannot be deleted.');

        $oldValues = $role->load('permissions')->toArray();
        $role->delete();

        $auditLogService->record('role.deleted', $role, $oldValues, null, request());

        return ApiResponse::success(message: 'Role deleted successfully.');
    }
}
