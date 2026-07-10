<?php

namespace Tests\Feature;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\Beneficiary;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\CaseFile;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_demo_supports_the_mvp_release_smoke_workflow(): void
    {
        $this->seed(DatabaseSeeder::class);

        $token = $this->tokenFor('admin@awniq.test');
        $branch = Branch::where('code', 'CAI')->firstOrFail();
        $warehouse = Warehouse::where('code', 'CAI-MAIN')->firstOrFail();
        $item = InventoryItem::where('sku', 'FOOD-RICE-5KG')->firstOrFail();
        $donor = Donor::where('email', 'layla.donor@example.test')->firstOrFail();

        $this->asToken($token)
            ->getJson('/api/v1/reports/dashboard')
            ->assertOk()
            ->assertJsonPath('data.metrics.active_campaigns', 2);

        $beneficiaryId = $this->asToken($token)
            ->postJson('/api/v1/beneficiaries', [
                'branch_id' => $branch->id,
                'full_name' => 'Release Smoke Beneficiary',
                'national_id' => 'EG-RELEASE-0001',
                'birth_date' => '1988-05-16',
                'gender' => 'female',
                'phone' => '+20 100 555 0001',
                'country' => 'Egypt',
                'city' => 'Cairo',
                'district' => 'Downtown',
                'address' => 'Fictional release smoke address',
                'marital_status' => 'widowed',
                'employment_status' => 'unemployed',
                'monthly_income' => 900,
                'household_size' => 3,
                'vulnerability_level' => 'high',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->json('data.id');

        $this->asToken($token)
            ->postJson("/api/v1/beneficiaries/{$beneficiaryId}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $this->asToken($token)
            ->postJson("/api/v1/beneficiaries/{$beneficiaryId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $caseFileId = $this->asToken($token)
            ->postJson('/api/v1/case-files', [
                'beneficiary_id' => $beneficiaryId,
                'case_type' => 'release_food_support',
                'priority' => 'high',
                'assessment_summary' => 'Release smoke test case for approved food support.',
                'next_follow_up_date' => now()->addWeek()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->json('data.id');

        $this->asToken($token)
            ->postJson("/api/v1/case-files/{$caseFileId}/submit-review")
            ->assertOk()
            ->assertJsonPath('data.status', 'under_review');

        $this->asToken($token)
            ->postJson("/api/v1/case-files/{$caseFileId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $campaignId = $this->asToken($token)
            ->postJson('/api/v1/campaigns', [
                'title' => 'Release Smoke Food Campaign',
                'slug' => 'release-smoke-food-campaign',
                'description' => 'Fictional campaign created by the release smoke test.',
                'goal_amount' => 10000,
                'currency' => 'EGP',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'status' => 'active',
                'visibility' => 'public',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.slug', 'release-smoke-food-campaign')
            ->json('data.id');

        $donationId = $this->asToken($token)
            ->postJson('/api/v1/donations', [
                'donor_id' => $donor->id,
                'campaign_id' => $campaignId,
                'amount' => 1500,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'donated_at' => now()->toISOString(),
                'allocations' => [
                    [
                        'allocation_type' => 'campaign',
                        'campaign_id' => $campaignId,
                        'amount' => 1500,
                        'notes' => 'Release smoke campaign allocation.',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending')
            ->json('data.id');

        $this->asToken($token)
            ->withHeader('Idempotency-Key', 'release-smoke-confirm-donation')
            ->postJson("/api/v1/donations/{$donationId}/confirm", [
                'provider' => 'manual',
                'provider_transaction_id' => 'RELEASE-SMOKE-DONATION',
            ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.donation_status', 'confirmed');

        $this->asToken($token)
            ->getJson("/api/v1/donations/{$donationId}/receipt")
            ->assertOk()
            ->assertJsonPath('data.status', 'issued');

        $this->asToken($token)
            ->postJson('/api/v1/stock/movements/receive', [
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $item->id,
                'quantity' => 30,
                'source_type' => 'donation_in_kind',
                'source_id' => 9901,
                'expiry_date' => now()->addMonths(9)->toDateString(),
                'received_at' => now()->toISOString(),
                'notes' => 'Release smoke stock receipt.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.remaining_quantity', '30.000');

        $aidBatchId = $this->asToken($token)
            ->postJson('/api/v1/aid-batches', [
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'title' => 'Release Smoke Food Batch',
                'description' => 'Release smoke batch from seeded demo data.',
                'campaign_id' => $campaignId,
                'scheduled_date' => now()->addDays(3)->toDateString(),
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'draft')
            ->json('data.id');

        $distributionId = $this->asToken($token)
            ->postJson("/api/v1/aid-batches/{$aidBatchId}/distributions", [
                'beneficiary_id' => $beneficiaryId,
                'case_file_id' => $caseFileId,
                'scheduled_at' => now()->addDays(3)->setTime(10, 0)->toISOString(),
                'delivery_method' => 'pickup',
                'notes' => 'Release smoke distribution.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->json('data.id');

        $this->asToken($token)
            ->postJson("/api/v1/aid-distributions/{$distributionId}/items", [
                'inventory_item_id' => $item->id,
                'quantity' => 5,
                'notes' => 'Release smoke rice allocation.',
            ])
            ->assertCreated();

        $this->asToken($token)
            ->postJson("/api/v1/aid-batches/{$aidBatchId}/submit-approval")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval');

        $this->asToken($token)
            ->withHeader('Idempotency-Key', 'release-smoke-approve-batch')
            ->postJson("/api/v1/aid-batches/{$aidBatchId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->asToken($token)
            ->withHeader('Idempotency-Key', 'release-smoke-deliver-distribution')
            ->postJson("/api/v1/aid-distributions/{$distributionId}/mark-delivered", [
                'proof_type' => 'manual',
                'notes' => 'Release smoke manual proof.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->asToken($token)
            ->getJson('/api/v1/reports/distributions')
            ->assertOk()
            ->assertJsonPath('data.summary.delivered_count', 1);

        $this->assertSame('confirmed', Donation::findOrFail($donationId)->donation_status);
        $this->assertSame('in_progress', AidBatch::findOrFail($aidBatchId)->status);
        $this->assertSame('delivered', AidDistribution::findOrFail($distributionId)->status);
        $this->assertSame('approved', Beneficiary::findOrFail($beneficiaryId)->status);
        $this->assertSame('approved', CaseFile::findOrFail($caseFileId)->status);
        $this->assertSame('1500.00', Campaign::findOrFail($campaignId)->collected_amount);
    }

    public function test_seeded_limited_roles_respect_release_authorization_boundaries(): void
    {
        $this->seed(DatabaseSeeder::class);

        $adminToken = $this->tokenFor('admin@awniq.test');
        $auditorToken = $this->tokenFor('auditor@awniq.test');
        $volunteerToken = $this->tokenFor('volunteer@awniq.test');
        $financeToken = $this->tokenFor('finance@awniq.test');
        $warehouseToken = $this->tokenFor('warehouse@awniq.test');
        $distributionToken = $this->tokenFor('distribution@awniq.test');

        $branch = Branch::where('code', 'CAI')->firstOrFail();
        $warehouse = Warehouse::where('code', 'CAI-MAIN')->firstOrFail();
        $item = InventoryItem::where('sku', 'FOOD-RICE-5KG')->firstOrFail();
        $campaign = Campaign::where('slug', 'ramadan-food-relief')->firstOrFail();
        $donor = Donor::where('email', 'layla.donor@example.test')->firstOrFail();
        $batch = AidBatch::where('batch_number', 'AID-000001')->firstOrFail();
        $distribution = AidDistribution::where('distribution_number', 'DIST-000001')->firstOrFail();

        $this->asToken($auditorToken)
            ->getJson('/api/v1/reports/audit-logs')
            ->assertOk();

        $this->asToken($auditorToken)
            ->postJson('/api/v1/beneficiaries', [
                'branch_id' => $branch->id,
                'full_name' => 'Auditor Forbidden Beneficiary',
            ])
            ->assertForbidden();

        $this->asToken($auditorToken)
            ->patchJson('/api/v1/settings/public-portal', ['enabled' => false])
            ->assertForbidden();

        $this->asToken($volunteerToken)
            ->getJson("/api/v1/aid-distributions/{$distribution->id}")
            ->assertOk();

        $this->asToken($volunteerToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'volunteer@awniq.test');

        $this->asToken($volunteerToken)
            ->getJson('/api/v1/audit-logs')
            ->assertForbidden();

        $this->asToken($volunteerToken)
            ->postJson("/api/v1/aid-batches/{$batch->id}/approve")
            ->assertForbidden();

        $this->asToken($financeToken)
            ->postJson('/api/v1/donations', [
                'donor_id' => $donor->id,
                'campaign_id' => $campaign->id,
                'amount' => 100,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'donated_at' => now()->toISOString(),
            ])
            ->assertOk();

        $this->asToken($financeToken)
            ->postJson('/api/v1/stock/movements/receive', [
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $item->id,
                'quantity' => 5,
                'source_type' => 'donation_in_kind',
                'source_id' => 9902,
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'received_at' => now()->toISOString(),
            ])
            ->assertForbidden();

        $this->asToken($warehouseToken)
            ->postJson('/api/v1/stock/movements/receive', [
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $item->id,
                'quantity' => 5,
                'source_type' => 'donation_in_kind',
                'source_id' => 9903,
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'received_at' => now()->toISOString(),
                'notes' => 'Warehouse role boundary stock receipt.',
            ])
            ->assertCreated();

        $this->asToken($warehouseToken)
            ->postJson('/api/v1/donations', [
                'amount' => 100,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'donated_at' => now()->toISOString(),
            ])
            ->assertForbidden();

        $this->asToken($adminToken)
            ->postJson("/api/v1/aid-batches/{$batch->id}/submit-approval")
            ->assertOk();

        $this->asToken($distributionToken)
            ->postJson("/api/v1/aid-batches/{$batch->id}/approve")
            ->assertForbidden();

        $this->asToken($distributionToken)
            ->getJson("/api/v1/aid-distributions/{$distribution->id}")
            ->assertOk();

        $this->assertDatabaseHas('stock_lots', [
            'source_type' => 'donation_in_kind',
            'source_id' => 9903,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'stock_in',
            'quantity' => 5,
        ]);
    }

    private function tokenFor(string $email): string
    {
        return User::where('email', $email)
            ->firstOrFail()
            ->createToken('release-readiness-test')
            ->plainTextToken;
    }

    private function asToken(string $token): static
    {
        auth()->forgetGuards();

        return $this->flushHeaders()->withToken($token);
    }
}
