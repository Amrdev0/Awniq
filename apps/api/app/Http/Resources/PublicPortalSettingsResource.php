<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPortalSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'enabled' => (bool) $this->resource['enabled'],
            'show_donation_totals' => (bool) $this->resource['show_donation_totals'],
            'show_campaign_progress' => (bool) $this->resource['show_campaign_progress'],
            'show_completed_campaigns' => (bool) $this->resource['show_completed_campaigns'],
            'show_contact_info' => (bool) $this->resource['show_contact_info'],
            'donations_enabled' => (bool) $this->resource['donations_enabled'],
            'reports_enabled' => (bool) $this->resource['reports_enabled'],
            'contact_email' => $this->resource['contact_email'],
            'contact_phone' => $this->resource['contact_phone'],
            'about' => $this->resource['about'],
        ];
    }
}
