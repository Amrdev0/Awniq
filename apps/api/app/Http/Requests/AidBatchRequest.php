<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AidBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('organization_id', $organizationId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('organization_id', $organizationId)],
            'title' => [$requiredOnCreate, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('organization_id', $organizationId)],
            'scheduled_date' => ['nullable', 'date'],
        ];
    }
}
