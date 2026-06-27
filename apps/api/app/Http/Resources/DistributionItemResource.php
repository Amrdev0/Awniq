<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'aid_distribution_id' => $this->aid_distribution_id,
            'inventory_item_id' => $this->inventory_item_id,
            'inventory_item' => new InventoryItemResource($this->whenLoaded('inventoryItem')),
            'stock_lot_id' => $this->stock_lot_id,
            'stock_lot' => new StockLotResource($this->whenLoaded('stockLot')),
            'quantity' => $this->quantity,
            'cash_amount' => $this->cash_amount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'reservations' => StockReservationResource::collection($this->whenLoaded('reservations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
