<?php

namespace Tests\Feature;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\Beneficiary;
use App\Models\Branch;
use App\Models\CaseFile;
use App\Models\InventoryItem;
use App\Models\StockLot;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AidDistributionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_list_aid_batches_and_eligible_beneficiaries(): void
    {
        $token = $this->seedAndLogin();
        $batch = AidBatch::where('batch_number', 'AID-000001')->firstOrFail();

        $this->withToken($token)
            ->getJson('/api/v1/aid-batches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['batch_number' => 'AID-000001']);

        $this->withToken($token)
            ->getJson("/api/v1/aid-batches/{$batch->id}/eligible-beneficiaries")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['code' => 'BEN-000001'])
            ->assertJsonMissing(['code' => 'BEN-000005']);

        $this->withToken($token)
            ->getJson("/api/v1/aid-batches/{$batch->id}/stock-check")
            ->assertOk()
            ->assertJsonFragment(['sku' => 'FOOD-RICE-5KG'])
            ->assertJsonFragment(['sufficient' => true]);
    }

    public function test_batch_approval_reserves_stock_idempotently(): void
    {
        $token = $this->seedAndLogin();
        $batch = AidBatch::where('batch_number', 'AID-000001')->firstOrFail();
        $lot = StockLot::where('source_type', 'opening_balance')->where('source_id', 1001)->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/submit-approval")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval');

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-approve-batch')
            ->postJson("/api/v1/aid-batches/{$batch->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('170.000', $lot->fresh()->remaining_quantity);
        $this->assertSame('10.000', $lot->fresh()->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', [
            'aid_batch_id' => $batch->id,
            'stock_lot_id' => $lot->id,
            'quantity' => 10,
            'status' => 'reserved',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'stock_lot_id' => $lot->id,
            'movement_type' => 'reserved',
            'quantity' => 10,
        ]);

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-approve-batch')
            ->postJson("/api/v1/aid-batches/{$batch->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('170.000', $lot->fresh()->remaining_quantity);
        $this->assertSame('10.000', $lot->fresh()->reserved_quantity);
        $this->assertSame(1, $batch->reservations()->count());
    }

    public function test_batch_cannot_be_approved_without_enough_stock(): void
    {
        $token = $this->seedAndLogin();
        $batchId = $this->createDraftBatchWithDistribution($token, 9999);

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batchId}/submit-approval")
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batchId}/approve")
            ->assertUnprocessable();

        $this->assertDatabaseMissing('stock_reservations', [
            'aid_batch_id' => $batchId,
            'status' => 'reserved',
        ]);
    }

    public function test_delivered_distribution_converts_reserved_stock_idempotently(): void
    {
        $token = $this->seedAndLogin();
        $batch = $this->approveSeededBatch($token);
        $distribution = AidDistribution::where('aid_batch_id', $batch->id)->firstOrFail();
        $lot = StockLot::where('source_type', 'opening_balance')->where('source_id', 1001)->firstOrFail();

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-deliver-distribution')
            ->postJson("/api/v1/aid-distributions/{$distribution->id}/mark-delivered", [
                'proof_type' => 'manual',
                'notes' => 'Feature test delivery confirmation.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertSame('170.000', $lot->fresh()->remaining_quantity);
        $this->assertSame('0.000', $lot->fresh()->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', [
            'aid_distribution_id' => $distribution->id,
            'status' => 'distributed',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'stock_lot_id' => $lot->id,
            'movement_type' => 'distributed',
            'quantity' => 10,
        ]);

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'feature-deliver-distribution')
            ->postJson("/api/v1/aid-distributions/{$distribution->id}/mark-delivered", [
                'proof_type' => 'manual',
                'notes' => 'Feature test delivery confirmation.',
            ])
            ->assertOk();

        $this->assertSame('0.000', $lot->fresh()->reserved_quantity);
        $this->assertSame(1, $distribution->reservations()->where('status', 'distributed')->count());
    }

    public function test_failed_distribution_releases_reserved_stock(): void
    {
        $token = $this->seedAndLogin();
        $batch = $this->approveSeededBatch($token);
        $distribution = AidDistribution::where('aid_batch_id', $batch->id)->firstOrFail();
        $lot = StockLot::where('source_type', 'opening_balance')->where('source_id', 1001)->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-distributions/{$distribution->id}/mark-failed", [
                'failure_reason' => 'Beneficiary was not available at the scheduled pickup time.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'failed');

        $this->assertSame('180.000', $lot->fresh()->remaining_quantity);
        $this->assertSame('0.000', $lot->fresh()->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', [
            'aid_distribution_id' => $distribution->id,
            'status' => 'released',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'stock_lot_id' => $lot->id,
            'movement_type' => 'released',
            'quantity' => 10,
        ]);
    }

    public function test_cancelled_batch_releases_reserved_stock(): void
    {
        $token = $this->seedAndLogin();
        $batch = $this->approveSeededBatch($token);
        $lot = StockLot::where('source_type', 'opening_balance')->where('source_id', 1001)->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('180.000', $lot->fresh()->remaining_quantity);
        $this->assertSame('0.000', $lot->fresh()->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', [
            'aid_batch_id' => $batch->id,
            'status' => 'released',
        ]);
    }

    public function test_completed_batch_requires_terminal_distributions(): void
    {
        $token = $this->seedAndLogin();
        $batch = $this->approveSeededBatch($token);
        $distribution = AidDistribution::where('aid_batch_id', $batch->id)->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/complete")
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson("/api/v1/aid-distributions/{$distribution->id}/mark-failed", [
                'failure_reason' => 'Beneficiary was not available at the scheduled pickup time.',
            ])
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_duplicate_beneficiary_in_same_batch_is_blocked(): void
    {
        $token = $this->seedAndLogin();
        $batch = AidBatch::where('batch_number', 'AID-000001')->firstOrFail();
        $beneficiary = Beneficiary::where('code', 'BEN-000001')->firstOrFail();
        $caseFile = CaseFile::where('case_number', 'CASE-000001')->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/distributions", [
                'beneficiary_id' => $beneficiary->id,
                'case_file_id' => $caseFile->id,
                'delivery_method' => 'pickup',
            ])
            ->assertUnprocessable();
    }

    private function approveSeededBatch(string $token): AidBatch
    {
        $batch = AidBatch::where('batch_number', 'AID-000001')->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/submit-approval")
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batch->id}/approve")
            ->assertOk();

        return $batch->fresh();
    }

    private function createDraftBatchWithDistribution(string $token, float $quantity): int
    {
        $branch = Branch::where('code', 'CAI')->firstOrFail();
        $warehouse = Warehouse::where('code', 'CAI-MAIN')->firstOrFail();
        $beneficiary = Beneficiary::where('code', 'BEN-000001')->firstOrFail();
        $caseFile = CaseFile::where('case_number', 'CASE-000001')->firstOrFail();
        $item = InventoryItem::where('sku', 'FOOD-RICE-5KG')->firstOrFail();

        $batchId = $this->withToken($token)
            ->postJson('/api/v1/aid-batches', [
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'title' => 'Feature Insufficient Stock Batch',
                'scheduled_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertCreated()
            ->json('data.id');

        $distributionId = $this->withToken($token)
            ->postJson("/api/v1/aid-batches/{$batchId}/distributions", [
                'beneficiary_id' => $beneficiary->id,
                'case_file_id' => $caseFile->id,
                'delivery_method' => 'pickup',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/aid-distributions/{$distributionId}/items", [
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
            ])
            ->assertCreated();

        return $batchId;
    }

    private function seedAndLogin(): string
    {
        $this->seed(DatabaseSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@awniq.test',
            'password' => 'Password123!',
            'device_name' => 'aid-distribution-feature-test',
        ]);

        $loginResponse->assertOk();

        return $loginResponse->json('data.token');
    }
}
