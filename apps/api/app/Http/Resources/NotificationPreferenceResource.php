<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category' => $this->resource['category'],
            'database_enabled' => (bool) $this->resource['database_enabled'],
            'email_enabled' => (bool) $this->resource['email_enabled'],
        ];
    }
}
