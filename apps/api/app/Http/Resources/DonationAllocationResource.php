<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'donation_id' => $this->donation_id,
            'allocation_type' => $this->allocation_type,
            'campaign_id' => $this->campaign_id,
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary' => new BeneficiaryResource($this->whenLoaded('beneficiary')),
            'case_file_id' => $this->case_file_id,
            'case_file' => new CaseFileResource($this->whenLoaded('caseFile')),
            'amount' => $this->amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
