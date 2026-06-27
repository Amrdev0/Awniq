<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'manager_user_id' => $this->manager_user_id,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'status' => $this->status,
            'stock_lots_count' => $this->whenCounted('stockLots'),
            'stock_movements_count' => $this->whenCounted('stockMovements'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
