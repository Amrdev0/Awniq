<?php

namespace Tests\Feature;

use App\Jobs\CaseFollowUpReminderJob;
use App\Jobs\ExpiringStockAlertJob;
use App\Jobs\LowStockAlertJob;
use App\Models\AidBatch;
use App\Models\Beneficiary;
use App\Models\CaseFile;
use App\Models\OperationalNotification;
use App\Models\Organization;
use App\Models\User;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationService;
use App\Services\StockReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_review_workflow_creates_safe_notification(): void
    {
        $token = $this->seedAndLogin('admin@awniq.test');
        $beneficiary = Beneficiary::where('code', 'BEN-000001')->firstOrFail();

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/case-files', [
                'beneficiary_id' => $beneficiary->id,
                'case_type' => 'notification_test',
                'priority' => 'medium',
                'assigned_to_user_id' => User::where('email', 'case.manager@awniq.test')->value('id'),
                'assessment_summary' => 'Notification test case.',
                'next_follow_up_date' => now()->addWeek()->toDateString(),
            ])
            ->assertCreated();

        $caseId = $createResponse->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/case-files/{$caseId}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', 'under_review');

        $caseManagerToken = $this->login('case.manager@awniq.test');

        $this->withToken($caseManagerToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonFragment(['type' => 'case.submitted_for_review'])
            ->assertJsonMissing(['national_id' => 'EG-DEMO-100001'])
            ->assertJsonMissing(['full_name' => 'Mariam Hassan']);
    }

    public function test_notification_list_count_and_mark_read_are_user_scoped(): void
    {
        $this->seed(DatabaseSeeder::class);
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();
        $admin = User::where('email', 'admin@awniq.test')->firstOrFail();
        $finance = User::where('email', 'finance@awniq.test')->firstOrFail();

        $ownNotification = OperationalNotification::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'type' => 'system.test',
            'category' => NotificationCategory::SYSTEM,
            'severity' => 'info',
            'title' => 'Own notification',
            'body' => 'Visible to admin.',
        ]);

        $otherNotification = OperationalNotification::create([
            'organization_id' => $organization->id,
            'user_id' => $finance->id,
            'type' => 'system.test',
            'category' => NotificationCategory::SYSTEM,
            'severity' => 'info',
            'title' => 'Other notification',
            'body' => 'Visible to finance only.',
        ]);

        $adminToken = $this->login('admin@awniq.test');

        $this->withToken($adminToken)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        $this->withToken($adminToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Own notification'])
            ->assertJsonMissing(['title' => 'Other notification']);

        $this->withToken($adminToken)
            ->postJson("/api/v1/notifications/{$otherNotification->id}/mark-read")
            ->assertNotFound();

        $this->withToken($adminToken)
            ->postJson("/api/v1/notifications/{$ownNotification->id}/mark-read")
            ->assertOk();

        $this->assertNotNull($ownNotification->fresh()->read_at);
    }

    public function test_notification_preferences_can_disable_database_delivery(): void
    {
        $this->seed(DatabaseSeeder::class);
        $caseManager = User::where('email', 'case.manager@awniq.test')->firstOrFail();
        $token = $this->login('case.manager@awniq.test');
        $caseFile = CaseFile::where('case_number', 'CASE-000001')->firstOrFail();
        $caseFile->update(['next_follow_up_date' => now()->subDay()->toDateString()]);

        $this->withToken($token)
            ->patchJson('/api/v1/notification-preferences', [
                'preferences' => [
                    [
                        'category' => NotificationCategory::CASES,
                        'database_enabled' => false,
                        'email_enabled' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'category' => NotificationCategory::CASES,
                'database_enabled' => false,
                'email_enabled' => false,
            ]);

        app(CaseFollowUpReminderJob::class)->handle(app(NotificationService::class));

        $this->assertDatabaseMissing('operational_notifications', [
            'user_id' => $caseManager->id,
            'type' => 'case.follow_up_due',
        ]);
    }

    public function test_scheduled_inventory_jobs_create_deduplicated_alerts(): void
    {
        $this->seed(DatabaseSeeder::class);

        app(LowStockAlertJob::class)->handle(app(NotificationService::class), app(StockReportService::class));
        $lowStockCount = OperationalNotification::where('type', 'inventory.low_stock')->count();
        $this->assertGreaterThan(0, $lowStockCount);

        app(LowStockAlertJob::class)->handle(app(NotificationService::class), app(StockReportService::class));
        $this->assertSame($lowStockCount, OperationalNotification::where('type', 'inventory.low_stock')->count());

        app(ExpiringStockAlertJob::class)->handle(app(NotificationService::class), app(StockReportService::class));
        $this->assertGreaterThan(0, OperationalNotification::where('type', 'inventory.expiring_stock')->count());
    }

    public function test_aid_batch_submit_and_approval_create_notifications(): void
    {
        $token = $this->seedAndLogin('admin@awniq.test');
        $batch = AidBatch::where('batch_number', 'AID-000001')->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/submit-approval")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval');

        $this->assertDatabaseHas('operational_notifications', [
            'type' => 'aid_batch.submitted_for_approval',
            'category' => NotificationCategory::AID_DISTRIBUTION,
        ]);

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'notify-approve-batch')
            ->postJson("/api/v1/aid-batches/{$batch->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('operational_notifications', [
            'type' => 'aid_batch.approved',
            'category' => NotificationCategory::AID_DISTRIBUTION,
        ]);

        $this->assertDatabaseHas('operational_notifications', [
            'type' => 'distribution.assigned',
            'category' => NotificationCategory::AID_DISTRIBUTION,
        ]);
    }

    public function test_system_queue_and_scheduler_health_are_available_to_admin(): void
    {
        $token = $this->seedAndLogin('admin@awniq.test');

        $this->withToken($token)
            ->getJson('/api/v1/system/scheduled-jobs')
            ->assertOk()
            ->assertJsonFragment(['name' => 'notifications.low-stock']);

        $this->withToken($token)
            ->getJson('/api/v1/system/queue-health')
            ->assertOk()
            ->assertJsonPath('data.connection', 'sync');
    }

    private function seedAndLogin(string $email): string
    {
        $this->seed(DatabaseSeeder::class);

        return $this->login($email);
    }

    private function login(string $email): string
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
            'device_name' => 'notification-feature-test',
        ]);

        $loginResponse->assertOk();

        return $loginResponse->json('data.token');
    }
}
