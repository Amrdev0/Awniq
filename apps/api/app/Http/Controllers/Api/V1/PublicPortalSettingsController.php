<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePublicPortalSettingsRequest;
use App\Http\Resources\PublicPortalSettingsResource;
use App\Services\AuditLogService;
use App\Services\PublicPortal\PublicPortalSettingsService;
use Illuminate\Http\Request;

class PublicPortalSettingsController extends Controller
{
    public function show(Request $request, PublicPortalSettingsService $settingsService): PublicPortalSettingsResource
    {
        return new PublicPortalSettingsResource($settingsService->get($request->user()->organization));
    }

    public function update(
        UpdatePublicPortalSettingsRequest $request,
        PublicPortalSettingsService $settingsService,
        AuditLogService $auditLogService,
    ): PublicPortalSettingsResource {
        $organization = $request->user()->organization;
        $oldSettings = $settingsService->get($organization);
        $settings = $settingsService->update($organization, $request->validated());

        $auditLogService->record(
            'public_portal_settings.updated',
            $organization,
            ['public_portal' => $oldSettings],
            ['public_portal' => $settings],
            $request,
        );

        return new PublicPortalSettingsResource($settings);
    }
}
