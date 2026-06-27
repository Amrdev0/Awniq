<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organization = $this->user()?->organization;

        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('organizations', 'slug')->ignore($organization?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'default_currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
            'language' => ['required', 'in:en,ar'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
