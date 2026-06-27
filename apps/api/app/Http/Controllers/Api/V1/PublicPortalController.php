<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicDonationRequest;
use App\Http\Resources\PublicCampaignResource;
use App\Http\Resources\PublicDonationIntentResource;
use App\Http\Resources\PublicOrganizationResource;
use App\Http\Resources\PublicStatsResource;
use App\Services\PublicPortal\PublicCampaignService;
use App\Services\PublicPortal\PublicDonationService;
use App\Services\PublicPortal\PublicPortalService;
use App\Services\PublicPortal\PublicPortalSettingsService;
use App\Services\PublicPortal\PublicStatsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicPortalController extends Controller
{
    public function organization(
        Request $request,
        PublicPortalService $portalService,
        PublicPortalSettingsService $settingsService,
    ): PublicOrganizationResource {
        $organization = $portalService->resolveOrganization($request->query('organization'));
        $settings = $settingsService->get($organization);

        $request->attributes->set('public_portal_settings', $settings);

        return new PublicOrganizationResource($organization);
    }

    public function campaigns(
        Request $request,
        PublicPortalService $portalService,
        PublicPortalSettingsService $settingsService,
        PublicCampaignService $campaignService,
    ): AnonymousResourceCollection {
        $organization = $portalService->resolveOrganization($request->query('organization'));
        $settings = $settingsService->get($organization);

        $request->attributes->set('public_portal_settings', $settings);

        $campaigns = $campaignService
            ->query($organization, $settings)
            ->when($request->query('search'), fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END")
            ->orderByDesc('start_date')
            ->paginate(min($request->integer('per_page', 12), 50));

        return PublicCampaignResource::collection($campaigns);
    }

    public function campaign(
        string $slug,
        Request $request,
        PublicPortalService $portalService,
        PublicPortalSettingsService $settingsService,
        PublicCampaignService $campaignService,
    ): PublicCampaignResource {
        $organization = $portalService->resolveOrganization($request->query('organization'));
        $settings = $settingsService->get($organization);
        $campaign = $campaignService->query($organization, $settings)->where('slug', $slug)->firstOrFail();

        $request->attributes->set('public_portal_settings', $settings);

        return new PublicCampaignResource($campaign);
    }

    public function stats(
        Request $request,
        PublicPortalService $portalService,
        PublicPortalSettingsService $settingsService,
        PublicStatsService $statsService,
    ): PublicStatsResource {
        $organization = $portalService->resolveOrganization($request->query('organization'));
        $settings = $settingsService->get($organization);

        return new PublicStatsResource($statsService->stats($organization, $settings));
    }

    public function reports(
        Request $request,
        PublicPortalService $portalService,
        PublicPortalSettingsService $settingsService,
        PublicStatsService $statsService,
        PublicCampaignService $campaignService,
    ): JsonResponse {
        $organization = $portalService->resolveOrganization($request->query('organization'));
        $settings = $settingsService->get($organization);

        abort_unless((bool) ($settings['reports_enabled'] ?? false), 404);

        $request->attributes->set('public_portal_settings', $settings);

        return ApiResponse::success([
            'stats' => (new PublicStatsResource($statsService->stats($organization, $settings)))->resolve($request),
            'campaigns' => PublicCampaignResource::collection(
                $campaignService->query($organization, $settings)->orderByDesc('start_date')->limit(10)->get(),
            )->resolve($request),
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function donate(
        PublicDonationRequest $request,
        PublicPortalService $portalService,
        PublicPortalSettingsService $settingsService,
        PublicDonationService $donationService,
    ): PublicDonationIntentResource {
        $organization = $portalService->resolveOrganization($request->validated('organization'));
        $settings = $settingsService->get($organization);
        $intent = $donationService->createIntent($organization, $settings, $request->payload());

        return new PublicDonationIntentResource($intent->load('campaign'));
    }
}
