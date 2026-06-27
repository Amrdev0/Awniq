<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(string $action, Model|string $entity, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null): AuditLog
    {
        $user = $request?->user();
        $entityType = is_string($entity) ? $entity : $entity::class;
        $entityId = is_string($entity) ? null : $entity->getKey();

        return AuditLog::create([
            'organization_id' => $user?->organization_id ?? ($entity instanceof Model ? $entity->getAttribute('organization_id') : null),
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
