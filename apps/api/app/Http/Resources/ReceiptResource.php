<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'donation_id' => $this->donation_id,
            'receipt_number' => $this->receipt_number,
            'file_path' => $this->file_path,
            'issued_at' => $this->issued_at?->toISOString(),
            'issued_by' => $this->issued_by,
            'issuer' => new UserResource($this->whenLoaded('issuer')),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
