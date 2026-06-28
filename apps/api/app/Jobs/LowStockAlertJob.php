<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Notifications\NotificationService;
use App\Services\StockReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LowStockAlertJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications, StockReportService $stockReportService): void
    {
        Organization::query()
            ->where('status', 'active')
            ->each(function (Organization $organization) use ($notifications, $stockReportService): void {
                foreach ($stockReportService->lowStock($organization->id) as $row) {
                    if (($row['status'] ?? null) !== 'active') {
                        continue;
                    }

                    $notifications->lowStock($organization->id, $row);
                }
            });
    }
}
