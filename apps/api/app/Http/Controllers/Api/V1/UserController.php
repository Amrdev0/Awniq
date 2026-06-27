<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $users = User::query()
            ->with(['organization', 'branch', 'roles'])
            ->where('organization_id', request()->user()->organization_id)
            ->when(request('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return UserResource::collection($users);
    }

    public function store(UserRequest $request, AuditLogService $auditLogService): UserResource
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user = User::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
        ]);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        $auditLogService->record('user.created', $user, null, $user->load('roles')->toArray(), $request);

        return new UserResource($user->load(['organization', 'branch', 'roles']));
    }

    public function show(User $user): UserResource
    {
        $this->assertUserScope($user);

        return new UserResource($user->load(['organization', 'branch', 'roles']));
    }

    public function update(UserRequest $request, User $user, AuditLogService $auditLogService): UserResource
    {
        $this->assertUserScope($user);

        $validated = $request->validated();
        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        if (($validated['password'] ?? null) === null) {
            unset($validated['password']);
        }

        $oldValues = $user->load('roles')->toArray();
        $user->update($validated);

        if (is_array($roles)) {
            $user->syncRoles($roles);
        }

        $auditLogService->record('user.updated', $user, $oldValues, $user->fresh()->load('roles')->toArray(), $request);

        return new UserResource($user->fresh()->load(['organization', 'branch', 'roles']));
    }

    public function destroy(User $user, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertUserScope($user);
        abort_if($user->id === request()->user()->id, 422, 'You cannot delete your own user account.');

        $oldValues = $user->load('roles')->toArray();
        $user->delete();

        $auditLogService->record('user.deleted', $user, $oldValues, null, request());

        return ApiResponse::success(message: 'User deleted successfully.');
    }

    public function enable(User $user, AuditLogService $auditLogService): UserResource
    {
        $this->assertUserScope($user);
        $oldValues = ['status' => $user->status];

        $user->update(['status' => 'active']);
        $auditLogService->record('user.enabled', $user, $oldValues, ['status' => 'active'], request());

        return new UserResource($user->fresh()->load(['organization', 'branch', 'roles']));
    }

    public function disable(User $user, AuditLogService $auditLogService): UserResource
    {
        $this->assertUserScope($user);
        abort_if($user->id === request()->user()->id, 422, 'You cannot disable your own user account.');

        $oldValues = ['status' => $user->status];
        $user->update(['status' => 'disabled']);
        $user->tokens()->delete();

        $auditLogService->record('user.disabled', $user, $oldValues, ['status' => 'disabled'], request());

        return new UserResource($user->fresh()->load(['organization', 'branch', 'roles']));
    }

    public function syncRoles(SyncUserRolesRequest $request, User $user, AuditLogService $auditLogService): UserResource
    {
        $this->assertUserScope($user);

        $oldValues = ['roles' => $user->roles->pluck('name')->values()->all()];
        $user->syncRoles($request->validated('roles'));

        $auditLogService->record('user.roles_synced', $user, $oldValues, ['roles' => $request->validated('roles')], $request);

        return new UserResource($user->fresh()->load(['organization', 'branch', 'roles']));
    }

    private function assertUserScope(User $user): void
    {
        abort_unless($user->organization_id === request()->user()->organization_id, 404);
    }
}
