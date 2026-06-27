<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'stock_lot_id' => $this->stock_lot_id,
            'stock_lot' => new StockLotResource($this->whenLoaded('stockLot')),
            'movement_type' => $this->movement_type,
            'quantity' => $this->quantity,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
