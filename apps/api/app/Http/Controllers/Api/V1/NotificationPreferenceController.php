<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\AuditLogService;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request, NotificationPreferenceService $preferences): AnonymousResourceCollection
    {
        return NotificationPreferenceResource::collection($preferences->listForUser($request->user()));
    }

    public function update(
        UpdateNotificationPreferencesRequest $request,
        NotificationPreferenceService $preferences,
        AuditLogService $auditLogService,
    ): AnonymousResourceCollection {
        $user = $request->user();
        $oldValues = $preferences->listForUser($user);
        $updated = $preferences->updateForUser($user, $request->preferences());

        $auditLogService->record(
            'notification_preferences.updated',
            $user,
            ['preferences' => $oldValues],
            ['preferences' => $updated],
            $request,
        );

        return NotificationPreferenceResource::collection($updated);
    }
}
