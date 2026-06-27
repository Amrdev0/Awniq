<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'aid_batch_id' => $this->aid_batch_id,
            'aid_distribution_id' => $this->aid_distribution_id,
            'distribution_item_id' => $this->distribution_item_id,
            'stock_lot_id' => $this->stock_lot_id,
            'stock_lot' => new StockLotResource($this->whenLoaded('stockLot')),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
