<?php

use App\Jobs\CaseFollowUpReminderJob;
use App\Jobs\ExpiringStockAlertJob;
use App\Jobs\LowStockAlertJob;
use App\Jobs\PendingBatchApprovalReminderJob;
use App\Jobs\PendingDonationReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new LowStockAlertJob)
    ->dailyAt('07:00')
    ->name('notifications.low-stock')
    ->withoutOverlapping();

Schedule::job(new ExpiringStockAlertJob(30))
    ->dailyAt('07:10')
    ->name('notifications.expiring-stock')
    ->withoutOverlapping();

Schedule::job(new CaseFollowUpReminderJob)
    ->dailyAt('08:00')
    ->name('notifications.case-follow-up')
    ->withoutOverlapping();

Schedule::job(new PendingBatchApprovalReminderJob)
    ->dailyAt('08:15')
    ->name('notifications.pending-batch-approval')
    ->withoutOverlapping();

Schedule::job(new PendingDonationReminderJob)
    ->dailyAt('08:30')
    ->name('notifications.pending-donation')
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
