<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $branchId = $this->route('branch')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'alpha_dash',
                'max:50',
                Rule::unique('branches', 'code')->where('organization_id', $organizationId)->ignore($branchId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'manager_user_id' => ['nullable', Rule::exists('users', 'id')->where('organization_id', $organizationId)],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
