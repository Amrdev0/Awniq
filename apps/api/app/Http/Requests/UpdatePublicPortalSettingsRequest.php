<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePublicPortalSettingsRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private array $allowedFields = [
        'enabled',
        'show_donation_totals',
        'show_campaign_progress',
        'show_completed_campaigns',
        'show_contact_info',
        'donations_enabled',
        'reports_enabled',
        'contact_email',
        'contact_phone',
        'about',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'show_donation_totals' => ['sometimes', 'boolean'],
            'show_campaign_progress' => ['sometimes', 'boolean'],
            'show_completed_campaigns' => ['sometimes', 'boolean'],
            'show_contact_info' => ['sometimes', 'boolean'],
            'donations_enabled' => ['sometimes', 'boolean'],
            'reports_enabled' => ['sometimes', 'boolean'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'about' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unexpected = collect(array_keys($this->all()))
                ->diff($this->allowedFields)
                ->values();

            if ($unexpected->isNotEmpty()) {
                $validator->errors()->add('payload', 'Unexpected fields: '.$unexpected->implode(', '));
            }
        });
    }
}
