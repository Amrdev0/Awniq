<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function scheduledJobs(): JsonResponse
    {
        return ApiResponse::success([
            'jobs' => [
                ['name' => 'notifications.low-stock', 'command' => 'LowStockAlertJob', 'frequency' => 'daily 07:00'],
                ['name' => 'notifications.expiring-stock', 'command' => 'ExpiringStockAlertJob', 'frequency' => 'daily 07:10'],
                ['name' => 'notifications.case-follow-up', 'command' => 'CaseFollowUpReminderJob', 'frequency' => 'daily 08:00'],
                ['name' => 'notifications.pending-batch-approval', 'command' => 'PendingBatchApprovalReminderJob', 'frequency' => 'daily 08:15'],
                ['name' => 'notifications.pending-donation', 'command' => 'PendingDonationReminderJob', 'frequency' => 'daily 08:30'],
            ],
        ]);
    }

    public function queueHealth(): JsonResponse
    {
        return ApiResponse::success([
            'connection' => (string) config('queue.default'),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ]);
    }
}
