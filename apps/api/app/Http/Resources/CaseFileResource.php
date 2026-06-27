<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary' => new BeneficiaryResource($this->whenLoaded('beneficiary')),
            'case_number' => $this->case_number,
            'case_type' => $this->case_type,
            'priority' => $this->priority,
            'status' => $this->status,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_to' => new UserResource($this->whenLoaded('assignedTo')),
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'reviewed_by' => new UserResource($this->whenLoaded('reviewedBy')),
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'rejection_reason' => $this->rejection_reason,
            'assessment_summary' => $this->assessment_summary,
            'next_follow_up_date' => $this->next_follow_up_date?->toDateString(),
            'notes' => CaseNoteResource::collection($this->whenLoaded('notes')),
            'documents' => CaseDocumentResource::collection($this->whenLoaded('documents')),
            'notes_count' => $this->whenCounted('notes'),
            'documents_count' => $this->whenCounted('documents'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
