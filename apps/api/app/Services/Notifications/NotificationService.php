<?php

namespace App\Services\Notifications;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\CaseFile;
use App\Models\Donation;
use App\Models\OperationalNotification;
use App\Models\Receipt;
use App\Models\StockLot;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferences,
        private readonly NotificationDeduplicationService $deduplication,
        private readonly NotificationRoutingService $routing,
    ) {}

    /**
     * @param  iterable<User>  $users
     * @param  array<string, mixed>  $data
     */
    public function notifyUsers(
        iterable $users,
        int $organizationId,
        string $type,
        string $category,
        string $severity,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $data = [],
        ?string $deduplicationKey = null,
        int $deduplicationHours = 24,
    ): int {
        if ($deduplicationKey && ! $this->deduplication->shouldSend($organizationId, $deduplicationKey, $deduplicationHours)) {
            return 0;
        }

        return collect($users)
            ->unique('id')
            ->filter(fn (User $user): bool => $user->organization_id === $organizationId && $this->preferences->databaseEnabled($user, $category))
            ->map(function (User $user) use ($organizationId, $type, $category, $severity, $title, $body, $actionUrl, $data): OperationalNotification {
                return OperationalNotification::create([
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'type' => $type,
                    'category' => $category,
                    'severity' => $severity,
                    'title' => $title,
                    'body' => $body,
                    'action_url' => $actionUrl,
                    'data' => $data,
                ]);
            })
            ->count();
    }

    public function caseSubmittedForReview(CaseFile $caseFile): int
    {
        $caseFile->loadMissing('beneficiary');

        return $this->notifyUsers(
            $this->routing->caseManagers($caseFile->organization_id, $caseFile->beneficiary?->branch_id),
            $caseFile->organization_id,
            'case.submitted_for_review',
            NotificationCategory::CASES,
            'warning',
            'Case submitted for review',
            "Case {$caseFile->case_number} is ready for review.",
            "/case-files/{$caseFile->id}",
            [
                'case_file_id' => $caseFile->id,
                'case_number' => $caseFile->case_number,
                'status' => $caseFile->status,
            ],
        );
    }

    public function caseDecision(CaseFile $caseFile, string $decision): int
    {
        $users = collect([$caseFile->assignedTo])->filter();

        if ($users->isEmpty()) {
            $caseFile->loadMissing('beneficiary');
            $users = $this->routing->caseManagers($caseFile->organization_id, $caseFile->beneficiary?->branch_id);
        }

        return $this->notifyUsers(
            $users,
            $caseFile->organization_id,
            "case.{$decision}",
            NotificationCategory::CASES,
            $decision === 'approved' ? 'success' : 'warning',
            'Case '.str_replace('_', ' ', $decision),
            "Case {$caseFile->case_number} was {$decision}.",
            "/case-files/{$caseFile->id}",
            [
                'case_file_id' => $caseFile->id,
                'case_number' => $caseFile->case_number,
                'status' => $caseFile->status,
            ],
        );
    }

    public function caseFollowUpDue(CaseFile $caseFile): int
    {
        $users = collect([$caseFile->assignedTo])->filter();

        if ($users->isEmpty()) {
            $caseFile->loadMissing('beneficiary');
            $users = $this->routing->caseManagers($caseFile->organization_id, $caseFile->beneficiary?->branch_id);
        }

        return $this->notifyUsers(
            $users,
            $caseFile->organization_id,
            'case.follow_up_due',
            NotificationCategory::CASES,
            $caseFile->next_follow_up_date?->isPast() ? 'critical' : 'warning',
            'Case follow-up due',
            "Case {$caseFile->case_number} has a follow-up date of {$caseFile->next_follow_up_date?->toDateString()}.",
            "/case-files/{$caseFile->id}",
            [
                'case_file_id' => $caseFile->id,
                'case_number' => $caseFile->case_number,
                'next_follow_up_date' => $caseFile->next_follow_up_date?->toDateString(),
            ],
            "case-follow-up:{$caseFile->id}:{$caseFile->next_follow_up_date?->toDateString()}",
        );
    }

    public function donationConfirmed(Donation $donation): int
    {
        return $this->notifyUsers(
            $this->routing->financeUsers($donation->organization_id),
            $donation->organization_id,
            'donation.confirmed',
            NotificationCategory::FINANCE,
            'success',
            'Donation confirmed',
            "Donation {$donation->donation_number} was confirmed.",
            "/donations/{$donation->id}",
            [
                'donation_id' => $donation->id,
                'donation_number' => $donation->donation_number,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
            ],
        );
    }

    public function receiptGenerated(Receipt $receipt): int
    {
        $receipt->loadMissing('donation');

        return $this->notifyUsers(
            $this->routing->financeUsers($receipt->organization_id),
            $receipt->organization_id,
            'receipt.generated',
            NotificationCategory::FINANCE,
            'success',
            'Receipt generated',
            "Receipt {$receipt->receipt_number} was generated.",
            "/donations/{$receipt->donation_id}/receipt",
            [
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'donation_id' => $receipt->donation_id,
                'donation_number' => $receipt->donation?->donation_number,
            ],
        );
    }

    public function pendingDonation(Donation $donation): int
    {
        return $this->notifyUsers(
            $this->routing->financeUsers($donation->organization_id),
            $donation->organization_id,
            'donation.pending_confirmation',
            NotificationCategory::FINANCE,
            'warning',
            'Donation awaiting confirmation',
            "Donation {$donation->donation_number} is still pending confirmation.",
            "/donations/{$donation->id}",
            [
                'donation_id' => $donation->id,
                'donation_number' => $donation->donation_number,
            ],
            "pending-donation:{$donation->id}:".now()->toDateString(),
        );
    }

    public function aidBatchSubmitted(AidBatch $batch): int
    {
        return $this->notifyUsers(
            $this->routing->warehouseUsers($batch->organization_id, $batch->branch_id),
            $batch->organization_id,
            'aid_batch.submitted_for_approval',
            NotificationCategory::AID_DISTRIBUTION,
            'warning',
            'Aid batch needs approval',
            "Aid batch {$batch->batch_number} was submitted for approval.",
            "/aid-batches/{$batch->id}",
            [
                'aid_batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'status' => $batch->status,
            ],
        );
    }

    public function aidBatchApproved(AidBatch $batch): int
    {
        return $this->notifyUsers(
            $this->routing->distributionUsers($batch->organization_id, $batch->branch_id),
            $batch->organization_id,
            'aid_batch.approved',
            NotificationCategory::AID_DISTRIBUTION,
            'success',
            'Aid batch approved',
            "Aid batch {$batch->batch_number} is ready for distribution.",
            "/aid-batches/{$batch->id}",
            [
                'aid_batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'status' => $batch->status,
            ],
        );
    }

    public function aidBatchCancelled(AidBatch $batch): int
    {
        return $this->notifyUsers(
            $this->routing->distributionUsers($batch->organization_id, $batch->branch_id)->merge($this->routing->warehouseUsers($batch->organization_id, $batch->branch_id)),
            $batch->organization_id,
            'aid_batch.cancelled',
            NotificationCategory::AID_DISTRIBUTION,
            'warning',
            'Aid batch cancelled',
            "Aid batch {$batch->batch_number} was cancelled.",
            "/aid-batches/{$batch->id}",
            [
                'aid_batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'status' => $batch->status,
            ],
        );
    }

    public function pendingBatchApproval(AidBatch $batch): int
    {
        return $this->notifyUsers(
            $this->routing->warehouseUsers($batch->organization_id, $batch->branch_id),
            $batch->organization_id,
            'aid_batch.pending_approval_reminder',
            NotificationCategory::AID_DISTRIBUTION,
            'warning',
            'Aid batch still awaiting approval',
            "Aid batch {$batch->batch_number} is still pending approval.",
            "/aid-batches/{$batch->id}",
            [
                'aid_batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
            ],
            "pending-batch:{$batch->id}:".now()->toDateString(),
        );
    }

    public function distributionAssigned(AidDistribution $distribution): int
    {
        $distribution->loadMissing('aidBatch');

        return $this->notifyUsers(
            $this->routing->distributionUsers($distribution->organization_id, $distribution->aidBatch?->branch_id),
            $distribution->organization_id,
            'distribution.assigned',
            NotificationCategory::AID_DISTRIBUTION,
            'info',
            'Distribution assigned',
            "Distribution {$distribution->distribution_number} is ready for delivery.",
            "/aid-distributions/{$distribution->id}",
            [
                'aid_distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'status' => $distribution->status,
            ],
        );
    }

    public function distributionChanged(AidDistribution $distribution, string $event): int
    {
        $distribution->loadMissing('aidBatch');

        return $this->notifyUsers(
            $this->routing->distributionUsers($distribution->organization_id, $distribution->aidBatch?->branch_id),
            $distribution->organization_id,
            "distribution.{$event}",
            NotificationCategory::AID_DISTRIBUTION,
            $event === 'failed' ? 'critical' : 'warning',
            'Distribution '.str_replace('_', ' ', $event),
            "Distribution {$distribution->distribution_number} was {$event}.",
            "/aid-distributions/{$distribution->id}",
            [
                'aid_distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'status' => $distribution->status,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function lowStock(int $organizationId, array $row): int
    {
        return $this->notifyUsers(
            $this->routing->warehouseUsers($organizationId),
            $organizationId,
            'inventory.low_stock',
            NotificationCategory::INVENTORY,
            'critical',
            'Low stock alert',
            "{$row['sku']} is at {$row['available_quantity']} {$row['unit']} available.",
            '/stock/low-stock',
            [
                'inventory_item_id' => $row['inventory_item_id'],
                'sku' => $row['sku'],
                'available_quantity' => $row['available_quantity'],
                'minimum_stock_level' => $row['minimum_stock_level'],
            ],
            "low-stock:{$row['inventory_item_id']}:".now()->toDateString(),
        );
    }

    public function expiringStock(StockLot $lot): int
    {
        $lot->loadMissing(['inventoryItem', 'warehouse']);

        return $this->notifyUsers(
            $this->routing->warehouseUsers($lot->organization_id, $lot->warehouse?->branch_id),
            $lot->organization_id,
            'inventory.expiring_stock',
            NotificationCategory::INVENTORY,
            'warning',
            'Stock expiring soon',
            "{$lot->inventoryItem?->sku} expires on {$lot->expiry_date?->toDateString()}.",
            '/stock/expiring',
            [
                'stock_lot_id' => $lot->id,
                'inventory_item_id' => $lot->inventory_item_id,
                'sku' => $lot->inventoryItem?->sku,
                'warehouse_id' => $lot->warehouse_id,
                'expiry_date' => $lot->expiry_date?->toDateString(),
            ],
            "expiring-stock:{$lot->id}:{$lot->expiry_date?->toDateString()}",
        );
    }

    /**
     * @return Collection<int, OperationalNotification>
     */
    public function recentForUser(User $user, int $limit = 10): Collection
    {
        return OperationalNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
