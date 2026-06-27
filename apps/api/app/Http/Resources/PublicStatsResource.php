<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_beneficiaries_helped' => $this->resource['total_beneficiaries_helped'],
            'total_aid_distributions' => $this->resource['total_aid_distributions'],
            'total_aid_items_distributed' => $this->resource['total_aid_items_distributed'],
            'total_confirmed_donations_collected' => $this->resource['total_confirmed_donations_collected'],
            'currency' => $this->resource['currency'],
            'active_campaigns' => $this->resource['active_campaigns'],
            'completed_campaigns' => $this->resource['completed_campaigns'],
            'generated_at' => $this->resource['generated_at'],
        ];
    }
}
