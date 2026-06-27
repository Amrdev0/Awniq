<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DonationConfirmationService
{
    public function __construct(private readonly ReceiptNumberGenerator $receiptNumberGenerator) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirm(Donation $donation, User $user, array $payload, ?string $idempotencyKey = null): Donation
    {
        return DB::transaction(function () use ($donation, $user, $payload, $idempotencyKey): Donation {
            $lockedDonation = Donation::query()
                ->whereKey($donation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDonation->isConfirmed()) {
                return $lockedDonation->load(['donor', 'campaign', 'allocations.campaign', 'allocations.beneficiary', 'allocations.caseFile', 'paymentTransactions', 'receipt.issuer']);
            }

            abort_if(in_array($lockedDonation->donation_status, ['cancelled', 'refunded'], true), 422, 'Cancelled or refunded donations cannot be confirmed.');

            $allocatedTotal = $this->normalizeMoney($lockedDonation->allocations()->sum('amount'));
            $donationAmount = $this->normalizeMoney($lockedDonation->amount);

            abort_unless($allocatedTotal === $donationAmount, 422, 'Donation allocation total must equal the donation amount before confirmation.');

            $paidAt = isset($payload['paid_at']) ? Carbon::parse($payload['paid_at']) : now();
            $provider = $payload['provider'] ?? 'manual';

            PaymentTransaction::create([
                'organization_id' => $lockedDonation->organization_id,
                'donation_id' => $lockedDonation->id,
                'provider' => $provider,
                'provider_transaction_id' => $payload['provider_transaction_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'amount' => $lockedDonation->amount,
                'currency' => $lockedDonation->currency,
                'status' => 'paid',
                'request_payload' => [
                    'source' => 'manual_confirmation',
                    'notes' => $payload['notes'] ?? null,
                ],
                'response_payload' => [
                    'message' => 'Payment confirmed.',
                ],
                'paid_at' => $paidAt,
            ]);

            $lockedDonation->update([
                'payment_status' => 'paid',
                'donation_status' => 'confirmed',
                'confirmed_at' => $paidAt,
            ]);

            foreach ($lockedDonation->allocations()->whereNotNull('campaign_id')->get() as $allocation) {
                Campaign::query()
                    ->whereKey($allocation->campaign_id)
                    ->lockForUpdate()
                    ->increment('collected_amount', $allocation->amount);
            }

            Receipt::firstOrCreate(
                ['donation_id' => $lockedDonation->id],
                [
                    'organization_id' => $lockedDonation->organization_id,
                    'receipt_number' => $this->receiptNumberGenerator->generate($lockedDonation->organization_id),
                    'issued_at' => now(),
                    'issued_by' => $user->id,
                    'status' => 'issued',
                ],
            );

            return $lockedDonation->fresh()->load(['donor', 'campaign', 'allocations.campaign', 'allocations.beneficiary', 'allocations.caseFile', 'paymentTransactions', 'receipt.issuer']);
        });
    }

    private function normalizeMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
