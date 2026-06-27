<?php

namespace App\Services\PublicPortal;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use Illuminate\Support\Facades\Cache;

class PublicPortalSettingsService
{
    public const KEY = 'public_portal';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'enabled' => false,
            'show_donation_totals' => false,
            'show_campaign_progress' => true,
            'show_completed_campaigns' => true,
            'show_contact_info' => false,
            'donations_enabled' => false,
            'reports_enabled' => false,
            'contact_email' => null,
            'contact_phone' => null,
            'about' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(Organization $organization): array
    {
        $setting = OrganizationSetting::query()
            ->where('organization_id', $organization->id)
            ->where('key', self::KEY)
            ->first();

        $value = is_array($setting?->value) ? $setting->value : [];

        return array_replace($this->defaults(), $value);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function update(Organization $organization, array $settings): array
    {
        $merged = array_replace($this->get($organization), array_intersect_key($settings, $this->defaults()));

        OrganizationSetting::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'key' => self::KEY,
            ],
            ['value' => $merged],
        );

        Cache::forget($this->statsCacheKey($organization->id));

        return $merged;
    }

    public function statsCacheKey(int $organizationId): string
    {
        return "public_portal:{$organizationId}:stats";
    }
}
