<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationAllocationRequest extends FormRequest
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
            'allocation_type' => [$requiredOnCreate, 'in:general_fund,campaign,beneficiary,case_file,medical,education,food,emergency,inventory,other'],
            'campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('organization_id', $organizationId)],
            'beneficiary_id' => ['nullable', Rule::exists('beneficiaries', 'id')->where('organization_id', $organizationId)],
            'case_file_id' => ['nullable', Rule::exists('case_files', 'id')->where('organization_id', $organizationId)],
            'amount' => [$requiredOnCreate, 'numeric', 'gt:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
