<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->with('user')
            ->where('organization_id', request()->user()->organization_id)
            ->when(request('action'), fn ($query, string $action) => $query->where('action', $action))
            ->when(request('entity_type'), fn ($query, string $entityType) => $query->where('entity_type', $entityType))
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('action', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->latest('created_at')
            ->paginate(request()->integer('per_page', 25));

        return AuditLogResource::collection($logs);
    }

    public function show(AuditLog $auditLog): AuditLogResource
    {
        abort_unless($auditLog->organization_id === request()->user()->organization_id, 404);

        return new AuditLogResource($auditLog->load('user'));
    }
}
