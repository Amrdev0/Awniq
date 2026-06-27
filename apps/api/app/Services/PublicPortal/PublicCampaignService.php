<?php

namespace App\Services\PublicPortal;

use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class PublicCampaignService
{
    /**
     * @param  array<string, mixed>  $settings
     * @return Builder<Campaign>
     */
    public function query(Organization $organization, array $settings): Builder
    {
        return Campaign::query()
            ->where('organization_id', $organization->id)
            ->where('visibility', 'public')
            ->whereIn('status', $this->visibleStatuses($settings));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<string>
     */
    private function visibleStatuses(array $settings): array
    {
        $statuses = ['active', 'paused'];

        if ((bool) ($settings['show_completed_campaigns'] ?? false)) {
            $statuses[] = 'completed';
        }

        return $statuses;
    }
}
