<?php

namespace Database\Seeders;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\Beneficiary;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\CaseFile;
use App\Models\DistributionItem;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoAidDistributionSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'hope-bridge-foundation')->firstOrFail();
        $cairoBranch = Branch::where('organization_id', $organization->id)->where('code', 'CAI')->firstOrFail();
        $cairoWarehouse = Warehouse::where('organization_id', $organization->id)->where('code', 'CAI-MAIN')->firstOrFail();
        $campaign = Campaign::where('organization_id', $organization->id)->where('slug', 'ramadan-food-relief')->firstOrFail();
        $caseManager = User::where('email', 'case.manager@awniq.test')->firstOrFail();
        $beneficiary = Beneficiary::where('organization_id', $organization->id)->where('code', 'BEN-000001')->firstOrFail();
        $caseFile = CaseFile::where('organization_id', $organization->id)->where('case_number', 'CASE-000001')->firstOrFail();
        $rice = InventoryItem::where('organization_id', $organization->id)->where('sku', 'FOOD-RICE-5KG')->firstOrFail();

        $batch = AidBatch::updateOrCreate(
            ['organization_id' => $organization->id, 'batch_number' => 'AID-000001'],
            [
                'branch_id' => $cairoBranch->id,
                'warehouse_id' => $cairoWarehouse->id,
                'title' => 'Demo Food Basket Batch',
                'description' => 'Draft batch for manually testing approval, reservation, and delivery.',
                'campaign_id' => $campaign->id,
                'scheduled_date' => now()->addWeek()->toDateString(),
                'status' => 'draft',
                'created_by' => $caseManager->id,
                'approved_by' => null,
                'approved_at' => null,
            ],
        );

        $distribution = AidDistribution::updateOrCreate(
            ['organization_id' => $organization->id, 'distribution_number' => 'DIST-000001'],
            [
                'aid_batch_id' => $batch->id,
                'beneficiary_id' => $beneficiary->id,
                'case_file_id' => $caseFile->id,
                'status' => 'draft',
                'scheduled_at' => now()->addWeek()->setTime(10, 0),
                'delivered_at' => null,
                'delivered_by' => null,
                'delivery_method' => 'pickup',
                'proof_type' => null,
                'proof_file_path' => null,
                'beneficiary_signature_path' => null,
                'otp_code' => null,
                'failure_reason' => null,
                'notes' => 'Seeded distribution awaiting approval.',
            ],
        );

        DistributionItem::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'aid_distribution_id' => $distribution->id,
                'inventory_item_id' => $rice->id,
            ],
            [
                'stock_lot_id' => null,
                'quantity' => 10,
                'cash_amount' => null,
                'currency' => null,
                'notes' => 'Food basket rice allocation.',
            ],
        );
    }
}
