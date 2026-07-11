<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperationalNotificationResource;
use App\Models\OperationalNotification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = OperationalNotification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('unread'), fn ($query) => $query->unread())
            ->when($request->query('category'), fn ($query, string $category) => $query->where('category', $category))
            ->when($request->query('severity'), fn ($query, string $severity) => $query->where('severity', $severity))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OperationalNotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'count' => OperationalNotification::query()
                ->where('user_id', $request->user()->id)
                ->unread()
                ->count(),
        ]);
    }

    public function markRead(OperationalNotification $notification): OperationalNotificationResource
    {
        $this->assertNotificationOwner($notification);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return new OperationalNotificationResource($notification->fresh());
    }

    public function markAllRead(Request $request): JsonResponse
    {
        OperationalNotification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return ApiResponse::success(message: 'Notifications marked as read.');
    }

    private function assertNotificationOwner(OperationalNotification $notification): void
    {
        abort_unless($notification->user_id === request()->user()->id, 404);
    }
}
