<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AidDistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'aid_batch_id' => $this->aid_batch_id,
            'aid_batch' => new AidBatchResource($this->whenLoaded('aidBatch')),
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary' => new BeneficiaryResource($this->whenLoaded('beneficiary')),
            'case_file_id' => $this->case_file_id,
            'case_file' => new CaseFileResource($this->whenLoaded('caseFile')),
            'distribution_number' => $this->distribution_number,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'delivered_by' => $this->delivered_by,
            'delivered_by_user' => new UserResource($this->whenLoaded('deliveredBy')),
            'delivery_method' => $this->delivery_method,
            'proof_type' => $this->proof_type,
            'proof_file_path' => $this->proof_file_path,
            'beneficiary_signature_path' => $this->beneficiary_signature_path,
            'otp_code' => $this->otp_code,
            'failure_reason' => $this->failure_reason,
            'notes' => $this->notes,
            'items' => DistributionItemResource::collection($this->whenLoaded('items')),
            'reservations' => StockReservationResource::collection($this->whenLoaded('reservations')),
            'items_count' => $this->whenCounted('items'),
            'reservations_count' => $this->whenCounted('reservations'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
