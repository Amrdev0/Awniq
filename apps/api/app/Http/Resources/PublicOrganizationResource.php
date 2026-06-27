<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicOrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = $request->attributes->get('public_portal_settings', []);
        $showContactInfo = (bool) ($settings['show_contact_info'] ?? false);

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'website' => $this->website,
            'logo' => $this->logo,
            'country' => $this->country,
            'city' => $this->city,
            'default_currency' => $this->default_currency,
            'language' => $this->language,
            'about' => $settings['about'] ?? null,
            'contact' => [
                'email' => $showContactInfo ? ($settings['contact_email'] ?? null) : null,
                'phone' => $showContactInfo ? ($settings['contact_phone'] ?? null) : null,
            ],
            'settings' => [
                'show_donation_totals' => (bool) ($settings['show_donation_totals'] ?? false),
                'show_campaign_progress' => (bool) ($settings['show_campaign_progress'] ?? false),
                'show_completed_campaigns' => (bool) ($settings['show_completed_campaigns'] ?? false),
                'donations_enabled' => (bool) ($settings['donations_enabled'] ?? false),
                'reports_enabled' => (bool) ($settings['reports_enabled'] ?? false),
            ],
        ];
    }
}
