<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationRequest extends FormRequest
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
            'donor_id' => ['nullable', Rule::exists('donors', 'id')->where('organization_id', $organizationId)],
            'campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('organization_id', $organizationId)],
            'amount' => [$requiredOnCreate, 'numeric', 'gt:0', 'max:9999999999.99'],
            'currency' => [$requiredOnCreate, 'string', 'size:3'],
            'payment_method' => [$requiredOnCreate, 'in:cash,bank_transfer,card,check,mobile_wallet,other'],
            'donation_status' => ['nullable', 'in:draft,pending'],
            'donated_at' => [$requiredOnCreate, 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.allocation_type' => ['required_with:allocations', 'in:general_fund,campaign,beneficiary,case_file,medical,education,food,emergency,inventory,other'],
            'allocations.*.campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('organization_id', $organizationId)],
            'allocations.*.beneficiary_id' => ['nullable', Rule::exists('beneficiaries', 'id')->where('organization_id', $organizationId)],
            'allocations.*.case_file_id' => ['nullable', Rule::exists('case_files', 'id')->where('organization_id', $organizationId)],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'gt:0', 'max:9999999999.99'],
            'allocations.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
