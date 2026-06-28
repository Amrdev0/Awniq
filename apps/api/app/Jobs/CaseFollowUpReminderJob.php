<?php

namespace App\Jobs;

use App\Models\CaseFile;
use App\Models\Organization;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CaseFollowUpReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Organization::query()
            ->where('status', 'active')
            ->each(function (Organization $organization) use ($notifications): void {
                CaseFile::query()
                    ->with(['assignedTo', 'beneficiary'])
                    ->where('organization_id', $organization->id)
                    ->whereIn('status', ['open', 'under_review', 'approved'])
                    ->whereNotNull('next_follow_up_date')
                    ->whereDate('next_follow_up_date', '<=', now()->toDateString())
                    ->each(fn (CaseFile $caseFile) => $notifications->caseFollowUpDue($caseFile));
            });
    }
}
