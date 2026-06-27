<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'donor_id' => $this->donor_id,
            'donor' => new DonorResource($this->whenLoaded('donor')),
            'campaign_id' => $this->campaign_id,
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'donation_number' => $this->donation_number,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'donation_status' => $this->donation_status,
            'donated_at' => $this->donated_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'allocations' => DonationAllocationResource::collection($this->whenLoaded('allocations')),
            'payment_transactions' => PaymentTransactionResource::collection($this->whenLoaded('paymentTransactions')),
            'receipt' => new ReceiptResource($this->whenLoaded('receipt')),
            'allocations_count' => $this->whenCounted('allocations'),
            'payment_transactions_count' => $this->whenCounted('paymentTransactions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
