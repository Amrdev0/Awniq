<?php

namespace App\Services;

use App\Models\AidBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AidBatchWorkflowService
{
    public function __construct(
        private readonly BeneficiaryEligibilityService $eligibilityService,
        private readonly StockReservationService $stockReservationService,
    ) {}

    public function submitForApproval(AidBatch $batch, User $user): AidBatch
    {
        return DB::transaction(function () use ($batch): AidBatch {
            $lockedBatch = AidBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedBatch->status === 'draft', 422, 'Only draft batches can be submitted for approval.');
            abort_unless($lockedBatch->distributions()->exists(), 422, 'A batch must include at least one distribution before approval.');

            $lockedBatch->update(['status' => 'pending_approval']);
            $lockedBatch->distributions()->where('status', 'draft')->update(['status' => 'pending_approval']);

            return $lockedBatch->fresh()->load(['branch', 'warehouse', 'campaign', 'creator'])->loadCount(['distributions', 'reservations']);
        });
    }

    public function approve(AidBatch $batch, User $user): AidBatch
    {
        return DB::transaction(function () use ($batch, $user): AidBatch {
            $lockedBatch = AidBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedBatch->status === 'pending_approval', 422, 'Only pending approval batches can be approved.');
            abort_unless($lockedBatch->distributions()->exists(), 422, 'A batch must include at least one distribution before approval.');
            abort_if($lockedBatch->warehouse_id === null && $lockedBatch->distributions()->whereHas('items', fn ($query) => $query->whereNotNull('inventory_item_id'))->exists(), 422, 'A warehouse is required before approving inventory distributions.');

            $lockedBatch->load(['distributions.beneficiary', 'distributions.caseFile', 'distributions.items']);

            foreach ($lockedBatch->distributions as $distribution) {
                $this->eligibilityService->assertEligible($distribution->beneficiary);
                $this->eligibilityService->assertCaseBelongsToBeneficiary($distribution->caseFile, $distribution->beneficiary);
                abort_unless($distribution->items->isNotEmpty(), 422, 'Every distribution must include at least one item or cash aid entry.');
            }

            $this->stockReservationService->reserveBatch($lockedBatch, $user);

            $lockedBatch->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            $lockedBatch->distributions()->whereIn('status', ['draft', 'pending_approval'])->update(['status' => 'approved']);

            return $lockedBatch->fresh()->load(['branch', 'warehouse', 'campaign', 'creator', 'approver'])->loadCount(['distributions', 'reservations']);
        });
    }

    public function cancel(AidBatch $batch, User $user): AidBatch
    {
        return DB::transaction(function () use ($batch, $user): AidBatch {
            $lockedBatch = AidBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($lockedBatch->status, ['completed', 'cancelled'], true), 422, 'Completed or cancelled batches cannot be cancelled again.');
            abort_if($lockedBatch->distributions()->where('status', 'delivered')->exists(), 422, 'A batch with delivered distributions cannot be cancelled.');

            $this->stockReservationService->releaseBatch($lockedBatch, $user);
            $lockedBatch->distributions()
                ->whereNotIn('status', ['delivered', 'failed', 'cancelled'])
                ->update(['status' => 'cancelled']);
            $lockedBatch->update(['status' => 'cancelled']);

            return $lockedBatch->fresh()->load(['branch', 'warehouse', 'campaign', 'creator', 'approver'])->loadCount(['distributions', 'reservations']);
        });
    }

    public function complete(AidBatch $batch): AidBatch
    {
        return DB::transaction(function () use ($batch): AidBatch {
            $lockedBatch = AidBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedBatch->status, ['approved', 'in_progress'], true), 422, 'Only approved or in-progress batches can be completed.');
            abort_unless($lockedBatch->distributions()->exists(), 422, 'A batch must include distributions before completion.');
            abort_if($lockedBatch->distributions()->whereNotIn('status', AidDistributionStatus::terminal())->exists(), 422, 'All distributions must be terminal before completing the batch.');
            abort_if($lockedBatch->reservations()->where('status', 'reserved')->exists(), 422, 'Reserved stock must be delivered or released before completion.');

            $lockedBatch->update(['status' => 'completed']);

            return $lockedBatch->fresh()->load(['branch', 'warehouse', 'campaign', 'creator', 'approver'])->loadCount(['distributions', 'reservations']);
        });
    }
}
