<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city,
            'address' => $this->address,
            'manager_user_id' => $this->manager_user_id,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
