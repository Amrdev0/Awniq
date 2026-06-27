<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'report_type' => ['required', 'in:donations,campaigns,beneficiaries,case_files,distributions,inventory,audit_logs'],
            'format' => ['nullable', 'in:csv'],
            'filters' => ['nullable', 'array'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
            'filters.branch_id' => ['nullable', Rule::exists('branches', 'id')->where('organization_id', $organizationId)],
            'filters.warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('organization_id', $organizationId)],
            'filters.campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('organization_id', $organizationId)],
            'filters.status' => ['nullable', 'string', 'max:120'],
            'filters.payment_method' => ['nullable', 'string', 'max:120'],
            'filters.donor_type' => ['nullable', 'string', 'max:120'],
            'filters.category' => ['nullable', 'string', 'max:120'],
        ];
    }
}
