<?php

namespace App\Jobs;

use App\Models\AidBatch;
use App\Models\Organization;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PendingBatchApprovalReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Organization::query()
            ->where('status', 'active')
            ->each(function (Organization $organization) use ($notifications): void {
                AidBatch::query()
                    ->where('organization_id', $organization->id)
                    ->where('status', 'pending_approval')
                    ->where('updated_at', '<=', now()->subDay())
                    ->each(fn (AidBatch $batch) => $notifications->pendingBatchApproval($batch));
            });
    }
}
