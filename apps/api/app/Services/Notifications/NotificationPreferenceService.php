<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user): array
    {
        $stored = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('category');

        return collect(NotificationCategory::all())
            ->map(function (string $category) use ($stored): array {
                $preference = $stored->get($category);

                return [
                    'category' => $category,
                    'database_enabled' => $preference?->database_enabled ?? true,
                    'email_enabled' => $preference?->email_enabled ?? false,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $preferences
     * @return list<array<string, mixed>>
     */
    public function updateForUser(User $user, array $preferences): array
    {
        foreach ($preferences as $preference) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category' => $preference['category'],
                ],
                [
                    'organization_id' => $user->organization_id,
                    'database_enabled' => (bool) ($preference['database_enabled'] ?? true),
                    'email_enabled' => false,
                ],
            );
        }

        return $this->listForUser($user);
    }

    public function databaseEnabled(User $user, string $category): bool
    {
        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category)
            ->first();

        return $preference?->database_enabled ?? true;
    }
}
