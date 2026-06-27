<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category,
            'unit' => $this->unit,
            'description' => $this->description,
            'minimum_stock_level' => $this->minimum_stock_level,
            'track_expiry' => $this->track_expiry,
            'status' => $this->status,
            'available_quantity' => $this->when(isset($this->available_quantity), $this->available_quantity),
            'reserved_quantity' => $this->when(isset($this->reserved_quantity), $this->reserved_quantity),
            'stock_lots' => StockLotResource::collection($this->whenLoaded('stockLots')),
            'stock_lots_count' => $this->whenCounted('stockLots'),
            'stock_movements_count' => $this->whenCounted('stockMovements'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
