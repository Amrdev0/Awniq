<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'code' => $this->code,
            'full_name' => $this->full_name,
            'national_id' => $this->national_id,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'marital_status' => $this->marital_status,
            'employment_status' => $this->employment_status,
            'monthly_income' => $this->monthly_income,
            'household_size' => $this->household_size,
            'vulnerability_level' => $this->vulnerability_level,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'reviewed_by' => new UserResource($this->whenLoaded('reviewedBy')),
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'rejection_reason' => $this->rejection_reason,
            'family_members' => BeneficiaryFamilyMemberResource::collection($this->whenLoaded('familyMembers')),
            'case_files' => CaseFileResource::collection($this->whenLoaded('caseFiles')),
            'family_members_count' => $this->whenCounted('familyMembers'),
            'case_files_count' => $this->whenCounted('caseFiles'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
