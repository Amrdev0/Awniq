<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AidDistributionRequest extends FormRequest
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
            'beneficiary_id' => [$requiredOnCreate, Rule::exists('beneficiaries', 'id')->where('organization_id', $organizationId)],
            'case_file_id' => ['nullable', Rule::exists('case_files', 'id')->where('organization_id', $organizationId)],
            'scheduled_at' => ['nullable', 'date'],
            'delivery_method' => [$requiredOnCreate, 'in:pickup,home_delivery,field_visit,partner_delivery,other'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
