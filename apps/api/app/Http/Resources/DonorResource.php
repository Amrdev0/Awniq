<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'donor_type' => $this->donor_type,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'city' => $this->city,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
            'notes' => $this->notes,
            'communication_preferences' => $this->communication_preferences,
            'status' => $this->status,
            'donations' => DonationResource::collection($this->whenLoaded('donations')),
            'donations_count' => $this->whenCounted('donations'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
