<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaseDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'in:identity,proof_of_address,medical_report,income_proof,assessment,consent,other'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }
}
