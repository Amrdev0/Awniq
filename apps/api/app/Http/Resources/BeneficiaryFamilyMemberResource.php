<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryFamilyMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'beneficiary_id' => $this->beneficiary_id,
            'full_name' => $this->full_name,
            'relationship' => $this->relationship,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'national_id' => $this->national_id,
            'education_level' => $this->education_level,
            'employment_status' => $this->employment_status,
            'health_notes' => $this->health_notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
