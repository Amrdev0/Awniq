<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_list_finance_records(): void
    {
        $token = $this->seedAndLogin();

        $this->withToken($token)
            ->getJson('/api/v1/donors')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['name' => 'Layla Mahmoud']);

        $this->withToken($token)
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['slug' => 'ramadan-food-relief']);

        $this->withToken($token)
            ->getJson('/api/v1/donations')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment(['donation_number' => 'DON-000001']);
    }

    public function test_donation_confirmation_creates_transaction_receipt_and_updates_campaign_once(): void
    {
        $token = $this->seedAndLogin();
        $donor = Donor::where('email', 'layla.donor@example.test')->firstOrFail();
        $campaign = Campaign::where('slug', 'ramadan-food-relief')->firstOrFail();
        $startingCollectedAmount = $campaign->collected_amount;

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/donations', [
                'donor_id' => $donor->id,
                'campaign_id' => $campaign->id,
                'amount' => 1000,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'donated_at' => now()->toISOString(),
                'allocations' => [
                    [
                        'allocation_type' => 'campaign',
                        'campaign_id' => $campaign->id,
                        'amount' => 1000,
                        'notes' => 'Feature test campaign allocation.',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.donation_status', 'pending');

        $donationId = $createResponse->json('data.id');

        $confirmPayload = [
            'provider' => 'manual',
            'provider_transaction_id' => 'FEATURE-MANUAL-0001',
        ];

        $firstConfirm = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-confirm-0001')
            ->postJson("/api/v1/donations/{$donationId}/confirm", $confirmPayload)
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.donation_status', 'confirmed')
            ->assertJsonPath('data.receipt.receipt_number', 'REC-000003');

        $this->assertDatabaseHas('payment_transactions', [
            'donation_id' => $donationId,
            'provider_transaction_id' => 'FEATURE-MANUAL-0001',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('receipts', [
            'donation_id' => $donationId,
            'receipt_number' => 'REC-000003',
        ]);

        $this->assertSame(
            number_format((float) $startingCollectedAmount + 1000, 2, '.', ''),
            Campaign::findOrFail($campaign->id)->collected_amount,
        );

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-confirm-0001')
            ->postJson("/api/v1/donations/{$donationId}/confirm", $confirmPayload)
            ->assertOk()
            ->assertJsonPath('data.receipt.receipt_number', $firstConfirm->json('data.receipt.receipt_number'));

        $this->assertSame(
            number_format((float) $startingCollectedAmount + 1000, 2, '.', ''),
            Campaign::findOrFail($campaign->id)->collected_amount,
        );
    }

    public function test_allocation_total_must_equal_donation_amount_before_confirmation(): void
    {
        $token = $this->seedAndLogin();
        $donor = Donor::where('email', 'layla.donor@example.test')->firstOrFail();

        $createResponse = $this->withToken($token)
            ->postJson('/api/v1/donations', [
                'donor_id' => $donor->id,
                'amount' => 1000,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'donated_at' => now()->toISOString(),
                'allocations' => [
                    [
                        'allocation_type' => 'general_fund',
                        'amount' => 500,
                    ],
                ],
            ])
            ->assertOk();

        $donationId = $createResponse->json('data.id');

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-confirm-mismatch')
            ->postJson("/api/v1/donations/{$donationId}/confirm", [
                'provider' => 'manual',
                'provider_transaction_id' => 'FEATURE-MANUAL-MISMATCH',
            ])
            ->assertUnprocessable();
    }

    public function test_cancelled_donation_cannot_be_confirmed(): void
    {
        $token = $this->seedAndLogin();
        $donation = Donation::where('donation_number', 'DON-000004')->firstOrFail();

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-confirm-cancelled')
            ->postJson("/api/v1/donations/{$donation->id}/confirm", [
                'provider' => 'manual',
                'provider_transaction_id' => 'FEATURE-MANUAL-CANCELLED',
            ])
            ->assertUnprocessable();
    }

    private function seedAndLogin(): string
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'finance-feature-test',
        ]);

        $loginResponse->assertOk();

        return $loginResponse->json('data.token');
    }
}
