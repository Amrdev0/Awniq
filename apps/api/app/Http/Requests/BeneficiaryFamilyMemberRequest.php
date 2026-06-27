<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BeneficiaryFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'full_name' => [$requiredOnCreate, 'string', 'max:255'],
            'relationship' => [$requiredOnCreate, 'string', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:male,female,other,unknown'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'education_level' => ['nullable', 'string', 'max:120'],
            'employment_status' => ['nullable', 'string', 'max:120'],
            'health_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
