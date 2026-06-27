<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $warehouseId = $this->route('warehouse')?->id;
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('organization_id', $organizationId)],
            'name' => [$requiredOnCreate, 'string', 'max:255'],
            'code' => [
                $requiredOnCreate,
                'alpha_dash',
                'max:50',
                Rule::unique('warehouses', 'code')->where('organization_id', $organizationId)->ignore($warehouseId),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'manager_user_id' => ['nullable', Rule::exists('users', 'id')->where('organization_id', $organizationId)],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
