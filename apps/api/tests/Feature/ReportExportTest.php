<?php

namespace Tests\Feature;

use App\Models\Export;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_core_reports_match_seeded_data(): void
    {
        $token = $this->seedAndLogin();

        $this->withToken($token)
            ->getJson('/api/v1/reports/dashboard')
            ->assertOk()
            ->assertJsonPath('data.metrics.total_donations_this_month', '8000.00')
            ->assertJsonPath('data.metrics.active_campaigns', 2)
            ->assertJsonPath('data.metrics.pending_cases', 2)
            ->assertJsonPath('data.metrics.approved_beneficiaries', 1)
            ->assertJsonPath('data.metrics.low_stock_items', 1)
            ->assertJsonPath('data.metrics.expiring_stock_lots', 1);

        $this->withToken($token)
            ->getJson('/api/v1/reports/donations')
            ->assertOk()
            ->assertJsonPath('data.summary.total_count', 5)
            ->assertJsonPath('data.summary.confirmed_count', 2)
            ->assertJsonPath('data.summary.confirmed_amount', '8000.00')
            ->assertJsonPath('data.summary.pending_count', 2)
            ->assertJsonPath('data.summary.cancelled_count', 1);

        $this->withToken($token)
            ->getJson('/api/v1/reports/beneficiaries')
            ->assertOk()
            ->assertJsonPath('data.summary.total_count', 5)
            ->assertJsonPath('data.summary.approved_count', 1)
            ->assertJsonPath('data.summary.pending_review_count', 1)
            ->assertJsonPath('data.summary.suspended_count', 1);

        $this->withToken($token)
            ->getJson('/api/v1/reports/distributions')
            ->assertOk()
            ->assertJsonPath('data.summary.total_count', 1)
            ->assertJsonPath('data.summary.delivered_count', 0);

        $this->withToken($token)
            ->getJson('/api/v1/reports/inventory')
            ->assertOk()
            ->assertJsonCount(4, 'data.stock_summary')
            ->assertJsonCount(1, 'data.low_stock');
    }

    public function test_csv_export_is_created_and_downloadable(): void
    {
        Storage::fake('local');
        $token = $this->seedAndLogin();

        $response = $this->withToken($token)
            ->postJson('/api/v1/exports', [
                'report_type' => 'donations',
                'format' => 'csv',
                'filters' => [
                    'date_from' => now()->startOfMonth()->toDateString(),
                    'date_to' => now()->endOfMonth()->toDateString(),
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.report_type', 'donations')
            ->assertJsonPath('data.status', 'completed');

        $export = Export::findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($export->file_path);

        $download = $this->withToken($token)
            ->get("/api/v1/exports/{$export->id}/download")
            ->assertOk();

        $csv = $download->streamedContent();
        $this->assertStringContainsString('donation_number', $csv);
        $this->assertStringContainsString('DON-000001', $csv);
    }

    public function test_export_requires_source_report_permission(): void
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'finance@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'finance-report-export-test',
        ]);

        $loginResponse->assertOk();
        $token = $loginResponse->json('data.token');

        $this->withToken($token)
            ->postJson('/api/v1/exports', [
                'report_type' => 'beneficiaries',
                'format' => 'csv',
            ])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_view_restricted_reports(): void
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'volunteer@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'volunteer-report-test',
        ]);

        $loginResponse->assertOk();

        $this->withToken($loginResponse->json('data.token'))
            ->getJson('/api/v1/reports/donations')
            ->assertForbidden();
    }

    private function seedAndLogin(): string
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'report-export-feature-test',
        ]);

        $loginResponse->assertOk();

        return $loginResponse->json('data.token');
    }
}
