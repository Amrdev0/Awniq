<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'goal_amount' => $this->goal_amount,
            'collected_amount' => $this->collected_amount,
            'currency' => $this->currency,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'cover_image' => $this->cover_image,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'donations' => DonationResource::collection($this->whenLoaded('donations')),
            'donations_count' => $this->whenCounted('donations'),
            'allocations_count' => $this->whenCounted('allocations'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
