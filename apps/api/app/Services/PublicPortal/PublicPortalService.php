<?php

namespace App\Services\PublicPortal;

use App\Models\Organization;

class PublicPortalService
{
    public function __construct(private readonly PublicPortalSettingsService $settingsService) {}

    public function resolveOrganization(?string $slug = null, bool $requireEnabled = true): Organization
    {
        $organization = Organization::query()
            ->where('status', 'active')
            ->when($slug, fn ($query, string $slug) => $query->where('slug', $slug))
            ->orderBy('id')
            ->first();

        abort_unless($organization, 404);

        if ($requireEnabled) {
            $settings = $this->settingsService->get($organization);
            abort_unless((bool) $settings['enabled'], 404);
        }

        return $organization;
    }
}
