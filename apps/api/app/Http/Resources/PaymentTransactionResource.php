<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'donation_id' => $this->donation_id,
            'provider' => $this->provider,
            'provider_transaction_id' => $this->provider_transaction_id,
            'idempotency_key' => $this->idempotency_key,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'paid_at' => $this->paid_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
