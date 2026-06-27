<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CaseFileRequest extends FormRequest
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
            'case_type' => [$requiredOnCreate, 'string', 'max:120'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'status' => ['nullable', 'in:open,under_review,approved,rejected,suspended,closed'],
            'assigned_to_user_id' => ['nullable', Rule::exists('users', 'id')->where('organization_id', $organizationId)],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
            'assessment_summary' => ['nullable', 'string', 'max:5000'],
            'next_follow_up_date' => ['nullable', 'date'],
        ];
    }
}
