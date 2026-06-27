<?php

namespace App\Services;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AidDeliveryService
{
    public function __construct(private readonly StockReservationService $stockReservationService) {}

    /**
     * @param  array<string, mixed>  $proofData
     */
    public function markDelivered(AidDistribution $distribution, User $user, array $proofData): AidDistribution
    {
        return DB::transaction(function () use ($distribution, $user, $proofData): AidDistribution {
            $lockedDistribution = AidDistribution::query()->whereKey($distribution->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedDistribution->status, ['approved', 'rescheduled'], true), 422, 'Only approved or rescheduled distributions can be delivered.');

            $this->stockReservationService->distribute($lockedDistribution, $user);

            $lockedDistribution->update([
                ...$proofData,
                'status' => 'delivered',
                'delivered_at' => now(),
                'delivered_by' => $user->id,
            ]);

            AidBatch::query()
                ->whereKey($lockedDistribution->aid_batch_id)
                ->where('status', 'approved')
                ->update(['status' => 'in_progress']);

            return $lockedDistribution->fresh()->load(['aidBatch', 'beneficiary', 'caseFile', 'deliveredBy', 'items.inventoryItem', 'items.stockLot', 'reservations.stockLot']);
        });
    }

    public function markFailed(AidDistribution $distribution, User $user, string $failureReason, ?string $notes = null): AidDistribution
    {
        return DB::transaction(function () use ($distribution, $user, $failureReason, $notes): AidDistribution {
            $lockedDistribution = AidDistribution::query()->whereKey($distribution->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedDistribution->status, ['approved', 'rescheduled'], true), 422, 'Only approved or rescheduled distributions can fail.');

            $batch = AidBatch::query()->whereKey($lockedDistribution->aid_batch_id)->lockForUpdate()->firstOrFail();
            $this->stockReservationService->releaseBatch($batch, $user, $lockedDistribution);

            $lockedDistribution->update([
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'notes' => $notes ?? $lockedDistribution->notes,
            ]);

            return $lockedDistribution->fresh()->load(['aidBatch', 'beneficiary', 'caseFile', 'deliveredBy', 'items.inventoryItem', 'items.stockLot', 'reservations.stockLot']);
        });
    }

    public function reschedule(AidDistribution $distribution, string $scheduledAt, ?string $notes = null): AidDistribution
    {
        return DB::transaction(function () use ($distribution, $scheduledAt, $notes): AidDistribution {
            $lockedDistribution = AidDistribution::query()->whereKey($distribution->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedDistribution->status, ['approved', 'rescheduled'], true), 422, 'Only approved or rescheduled distributions can be rescheduled.');

            $lockedDistribution->update([
                'status' => 'rescheduled',
                'scheduled_at' => $scheduledAt,
                'notes' => $notes ?? $lockedDistribution->notes,
            ]);

            return $lockedDistribution->fresh()->load(['aidBatch', 'beneficiary', 'caseFile', 'deliveredBy', 'items.inventoryItem', 'items.stockLot', 'reservations.stockLot']);
        });
    }

    /**
     * @param  array<string, mixed>  $proofData
     */
    public function attachProof(AidDistribution $distribution, array $proofData): AidDistribution
    {
        $distribution->update($proofData);

        return $distribution->fresh()->load(['aidBatch', 'beneficiary', 'caseFile', 'deliveredBy', 'items.inventoryItem', 'items.stockLot', 'reservations.stockLot']);
    }
}
