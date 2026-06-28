<?php

namespace App\Jobs;

use App\Models\Donation;
use App\Models\Organization;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PendingDonationReminderJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Organization::query()
            ->where('status', 'active')
            ->each(function (Organization $organization) use ($notifications): void {
                Donation::query()
                    ->where('organization_id', $organization->id)
                    ->where('payment_status', 'pending')
                    ->whereIn('donation_status', ['pending', 'draft'])
                    ->where('donated_at', '<=', now()->subDays(2))
                    ->each(fn (Donation $donation) => $notifications->pendingDonation($donation));
            });
    }
}
