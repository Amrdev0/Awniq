<?php

namespace App\Http\Requests;

use App\Services\Notifications\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.category' => ['required', 'string', Rule::in(NotificationCategory::all())],
            'preferences.*.database_enabled' => ['sometimes', 'boolean'],
            'preferences.*.email_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function preferences(): array
    {
        return $this->validated('preferences');
    }
}
