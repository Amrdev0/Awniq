<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CaseFile;
use App\Models\Donation;
use App\Models\DonationAllocation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\PaymentTransaction;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();
        $admin = User::where('email', 'admin@awniq.test')->firstOrFail();
        $finance = User::where('email', 'finance@awniq.test')->firstOrFail();

        $donors = $this->seedDonors($organization);
        $campaigns = $this->seedCampaigns($organization, $finance);
        $caseFile = CaseFile::where('organization_id', $organization->id)->where('case_number', 'CASE-000001')->firstOrFail();
        $maxCurrentMonthDaysBack = max(0, now()->day - 1);
        $withinCurrentMonth = fn (int $days) => now()->subDays(min($days, $maxCurrentMonthDaysBack));

        $donationRows = [
            [
                'donation_number' => 'DON-000001',
                'donor_key' => 'individual',
                'campaign_key' => 'ramadan-food-relief',
                'amount' => 5000,
                'currency' => 'EGP',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
                'donation_status' => 'confirmed',
                'donated_at' => $withinCurrentMonth(5),
                'confirmed_at' => $withinCurrentMonth(4),
                'notes' => 'Seeded confirmed campaign donation.',
                'allocations' => [
                    ['allocation_type' => 'campaign', 'campaign_key' => 'ramadan-food-relief', 'amount' => 5000, 'notes' => 'Campaign support'],
                ],
                'transaction' => ['provider_transaction_id' => 'MANUAL-DEMO-0001'],
                'receipt_number' => 'REC-000001',
            ],
            [
                'donation_number' => 'DON-000002',
                'donor_key' => 'company',
                'campaign_key' => 'medical-support',
                'amount' => 7500,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'donation_status' => 'pending',
                'donated_at' => $withinCurrentMonth(3),
                'confirmed_at' => null,
                'notes' => 'Pending donation awaiting manual confirmation.',
                'allocations' => [
                    ['allocation_type' => 'campaign', 'campaign_key' => 'medical-support', 'amount' => 5000, 'notes' => 'Medical campaign allocation'],
                    ['allocation_type' => 'general_fund', 'amount' => 2500, 'notes' => 'Flexible operating support'],
                ],
                'transaction' => null,
                'receipt_number' => null,
            ],
            [
                'donation_number' => 'DON-000003',
                'donor_key' => 'institution',
                'campaign_key' => null,
                'amount' => 3000,
                'currency' => 'EGP',
                'payment_method' => 'mobile_wallet',
                'payment_status' => 'paid',
                'donation_status' => 'confirmed',
                'donated_at' => $withinCurrentMonth(2),
                'confirmed_at' => $withinCurrentMonth(2),
                'notes' => 'Seeded confirmed case-specific donation.',
                'allocations' => [
                    ['allocation_type' => 'case_file', 'case_file_id' => $caseFile->id, 'amount' => 3000, 'notes' => 'Approved case support'],
                ],
                'transaction' => ['provider_transaction_id' => 'MANUAL-DEMO-0003'],
                'receipt_number' => 'REC-000002',
            ],
            [
                'donation_number' => 'DON-000004',
                'donor_key' => 'anonymous',
                'campaign_key' => 'winter-supplies',
                'amount' => 1200,
                'currency' => 'EGP',
                'payment_method' => 'cash',
                'payment_status' => 'cancelled',
                'donation_status' => 'cancelled',
                'donated_at' => $withinCurrentMonth(1),
                'confirmed_at' => null,
                'notes' => 'Cancelled demo donation.',
                'allocations' => [
                    ['allocation_type' => 'campaign', 'campaign_key' => 'winter-supplies', 'amount' => 1200, 'notes' => 'Cancelled allocation'],
                ],
                'transaction' => null,
                'receipt_number' => null,
            ],
            [
                'donation_number' => 'DON-000005',
                'donor_key' => 'individual',
                'campaign_key' => null,
                'amount' => 1800,
                'currency' => 'EGP',
                'payment_method' => 'check',
                'payment_status' => 'pending',
                'donation_status' => 'draft',
                'donated_at' => now(),
                'confirmed_at' => null,
                'notes' => 'Draft donation for manual testing.',
                'allocations' => [
                    ['allocation_type' => 'food', 'amount' => 1800, 'notes' => 'Food aid allocation'],
                ],
                'transaction' => null,
                'receipt_number' => null,
            ],
        ];

        foreach ($donationRows as $row) {
            $donation = Donation::updateOrCreate(
                ['organization_id' => $organization->id, 'donation_number' => $row['donation_number']],
                [
                    'organization_id' => $organization->id,
                    'donor_id' => $donors[$row['donor_key']]->id,
                    'campaign_id' => $row['campaign_key'] ? $campaigns[$row['campaign_key']]->id : null,
                    'amount' => $row['amount'],
                    'currency' => $row['currency'],
                    'payment_method' => $row['payment_method'],
                    'payment_status' => $row['payment_status'],
                    'donation_status' => $row['donation_status'],
                    'donated_at' => $row['donated_at'],
                    'confirmed_at' => $row['confirmed_at'],
                    'notes' => $row['notes'],
                    'created_by' => $finance->id,
                ],
            );

            $donation->allocations()->delete();

            foreach ($row['allocations'] as $allocationRow) {
                DonationAllocation::create([
                    'organization_id' => $organization->id,
                    'donation_id' => $donation->id,
                    'allocation_type' => $allocationRow['allocation_type'],
                    'campaign_id' => isset($allocationRow['campaign_key']) ? $campaigns[$allocationRow['campaign_key']]->id : null,
                    'beneficiary_id' => $allocationRow['beneficiary_id'] ?? null,
                    'case_file_id' => $allocationRow['case_file_id'] ?? null,
                    'amount' => $allocationRow['amount'],
                    'notes' => $allocationRow['notes'] ?? null,
                ]);
            }

            if ($row['transaction']) {
                PaymentTransaction::updateOrCreate(
                    ['provider' => 'manual', 'provider_transaction_id' => $row['transaction']['provider_transaction_id']],
                    [
                        'organization_id' => $organization->id,
                        'donation_id' => $donation->id,
                        'idempotency_key' => null,
                        'amount' => $donation->amount,
                        'currency' => $donation->currency,
                        'status' => 'paid',
                        'request_payload' => ['source' => 'demo_seed'],
                        'response_payload' => ['message' => 'Seeded payment transaction.'],
                        'paid_at' => $donation->confirmed_at,
                    ],
                );
            }

            if ($row['receipt_number']) {
                Receipt::updateOrCreate(
                    ['donation_id' => $donation->id],
                    [
                        'organization_id' => $organization->id,
                        'receipt_number' => $row['receipt_number'],
                        'issued_at' => $donation->confirmed_at,
                        'issued_by' => $admin->id,
                        'status' => 'issued',
                    ],
                );
            }
        }

        $this->recalculateCampaignTotals($organization);
    }

    /**
     * @return array<string, Donor>
     */
    private function seedDonors(Organization $organization): array
    {
        return [
            'individual' => Donor::updateOrCreate(
                ['organization_id' => $organization->id, 'email' => 'layla.donor@example.test'],
                [
                    'donor_type' => 'individual',
                    'name' => 'Layla Mahmoud',
                    'phone' => '+20 100 222 0001',
                    'country' => 'Egypt',
                    'city' => 'Cairo',
                    'address' => 'Demo donor address 1',
                    'status' => 'active',
                ],
            ),
            'company' => Donor::updateOrCreate(
                ['organization_id' => $organization->id, 'email' => 'csr@greenvalley.test'],
                [
                    'donor_type' => 'company',
                    'name' => 'Green Valley Foods',
                    'phone' => '+20 100 222 0002',
                    'country' => 'Egypt',
                    'city' => 'Giza',
                    'address' => 'Demo donor address 2',
                    'tax_number' => 'TAX-DEMO-2002',
                    'status' => 'active',
                ],
            ),
            'institution' => Donor::updateOrCreate(
                ['organization_id' => $organization->id, 'email' => 'giving@northstar.test'],
                [
                    'donor_type' => 'institution',
                    'name' => 'North Star Giving Fund',
                    'phone' => '+20 100 222 0003',
                    'country' => 'Egypt',
                    'city' => 'Alexandria',
                    'address' => 'Demo donor address 3',
                    'status' => 'active',
                ],
            ),
            'anonymous' => Donor::updateOrCreate(
                ['organization_id' => $organization->id, 'name' => 'Anonymous Donor'],
                [
                    'donor_type' => 'anonymous',
                    'email' => null,
                    'phone' => null,
                    'status' => 'active',
                ],
            ),
        ];
    }

    /**
     * @return array<string, Campaign>
     */
    private function seedCampaigns(Organization $organization, User $finance): array
    {
        return [
            'ramadan-food-relief' => Campaign::updateOrCreate(
                ['organization_id' => $organization->id, 'slug' => 'ramadan-food-relief'],
                [
                    'title' => 'Ramadan Food Relief',
                    'description' => 'Demo campaign for monthly food baskets.',
                    'goal_amount' => 50000,
                    'currency' => 'EGP',
                    'start_date' => now()->subMonth()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'status' => 'active',
                    'visibility' => 'public',
                    'created_by' => $finance->id,
                ],
            ),
            'medical-support' => Campaign::updateOrCreate(
                ['organization_id' => $organization->id, 'slug' => 'medical-support'],
                [
                    'title' => 'Medical Support Fund',
                    'description' => 'Demo campaign for verified medical assistance cases.',
                    'goal_amount' => 75000,
                    'currency' => 'EGP',
                    'start_date' => now()->subWeeks(2)->toDateString(),
                    'end_date' => now()->addMonths(2)->toDateString(),
                    'status' => 'active',
                    'visibility' => 'private',
                    'created_by' => $finance->id,
                ],
            ),
            'winter-supplies' => Campaign::updateOrCreate(
                ['organization_id' => $organization->id, 'slug' => 'winter-supplies'],
                [
                    'title' => 'Winter Supplies',
                    'description' => 'Demo paused campaign for blankets and seasonal aid.',
                    'goal_amount' => 30000,
                    'currency' => 'EGP',
                    'start_date' => now()->subMonth()->toDateString(),
                    'end_date' => now()->addMonths(3)->toDateString(),
                    'status' => 'paused',
                    'visibility' => 'private',
                    'created_by' => $finance->id,
                ],
            ),
        ];
    }

    private function recalculateCampaignTotals(Organization $organization): void
    {
        Campaign::where('organization_id', $organization->id)->update(['collected_amount' => 0]);

        $totals = DonationAllocation::query()
            ->select('donation_allocations.campaign_id', DB::raw('SUM(donation_allocations.amount) as total'))
            ->join('donations', 'donation_allocations.donation_id', '=', 'donations.id')
            ->where('donation_allocations.organization_id', $organization->id)
            ->whereNotNull('donation_allocations.campaign_id')
            ->where('donations.donation_status', 'confirmed')
            ->where('donations.payment_status', 'paid')
            ->groupBy('donation_allocations.campaign_id')
            ->get();

        foreach ($totals as $total) {
            Campaign::whereKey($total->campaign_id)->update(['collected_amount' => $total->total]);
        }
    }
}
