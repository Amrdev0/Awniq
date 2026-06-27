<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'inventory_item_id' => $this->inventory_item_id,
            'inventory_item' => new InventoryItemResource($this->whenLoaded('inventoryItem')),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'quantity' => $this->quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'received_at' => $this->received_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
