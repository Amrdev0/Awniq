<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = $request->attributes->get('public_portal_settings', []);
        $showDonationTotals = (bool) ($settings['show_donation_totals'] ?? false);
        $showProgress = (bool) ($settings['show_campaign_progress'] ?? false);
        $goalAmount = (float) $this->goal_amount;
        $collectedAmount = (float) $this->collected_amount;

        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'currency' => $this->currency,
            'status' => $this->status,
            'cover_image' => $this->cover_image,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'goal_amount' => $showProgress ? $this->goal_amount : null,
            'collected_amount' => $showDonationTotals ? $this->collected_amount : null,
            'progress_percentage' => $showProgress && $showDonationTotals && $goalAmount > 0
                ? round(min(($collectedAmount / $goalAmount) * 100, 100), 2)
                : null,
            'donations_enabled' => (bool) ($settings['donations_enabled'] ?? false) && $this->status === 'active',
        ];
    }
}
