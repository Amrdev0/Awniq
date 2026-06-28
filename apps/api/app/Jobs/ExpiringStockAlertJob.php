<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Notifications\NotificationService;
use App\Services\StockReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpiringStockAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $days = 30) {}

    public function handle(NotificationService $notifications, StockReportService $stockReportService): void
    {
        Organization::query()
            ->where('status', 'active')
            ->each(function (Organization $organization) use ($notifications, $stockReportService): void {
                foreach ($stockReportService->expiring($organization->id, $this->days) as $lot) {
                    $notifications->expiringStock($lot);
                }
            });
    }
}
