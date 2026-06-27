<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $beneficiaryId = $this->route('beneficiary')?->id;
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'branch_id' => [$requiredOnCreate, Rule::exists('branches', 'id')->where('organization_id', $organizationId)],
            'full_name' => [$requiredOnCreate, 'string', 'max:255'],
            'national_id' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('beneficiaries', 'national_id')->where('organization_id', $organizationId)->ignore($beneficiaryId),
            ],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:male,female,other,unknown'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'marital_status' => ['nullable', 'in:single,married,widowed,divorced,separated,unknown'],
            'employment_status' => ['nullable', 'in:employed,unemployed,self_employed,student,retired,unable_to_work,unknown'],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'household_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'vulnerability_level' => ['nullable', 'in:low,medium,high,critical'],
            'status' => ['nullable', 'in:draft,pending_review,approved,rejected,suspended,archived'],
        ];
    }
}
