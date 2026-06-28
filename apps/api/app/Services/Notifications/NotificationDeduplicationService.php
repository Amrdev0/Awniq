<?php

namespace App\Services\Notifications;

use App\Models\NotificationDeduplicationKey;
use Illuminate\Support\Facades\DB;

class NotificationDeduplicationService
{
    public function shouldSend(int $organizationId, string $key, int $cooldownHours = 24): bool
    {
        return DB::transaction(function () use ($organizationId, $key, $cooldownHours): bool {
            $record = NotificationDeduplicationKey::query()
                ->where('organization_id', $organizationId)
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($record && $record->last_sent_at && $record->last_sent_at->greaterThan(now()->subHours($cooldownHours))) {
                return false;
            }

            NotificationDeduplicationKey::updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'key' => $key,
                ],
                ['last_sent_at' => now()],
            );

            return true;
        });
    }
}
