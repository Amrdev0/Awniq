<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicDonationIntentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'campaign' => $this->campaign ? [
                'title' => $this->campaign->title,
                'slug' => $this->campaign->slug,
            ] : null,
            'message' => 'Donation intent recorded. Payment processing is not enabled in this MVP flow.',
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
