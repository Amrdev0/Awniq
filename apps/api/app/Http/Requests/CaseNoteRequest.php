<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaseNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'note' => [$requiredOnCreate, 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:internal,private,public'],
        ];
    }
}
