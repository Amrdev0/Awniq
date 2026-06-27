<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('organization_id', $organizationId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('organization_id', $organizationId)],
            'campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('organization_id', $organizationId)],
            'status' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,card,check,mobile_wallet,other'],
            'donor_type' => ['nullable', 'in:individual,company,institution,anonymous'],
            'category' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return collect($this->validated())
            ->except('per_page')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }
}
