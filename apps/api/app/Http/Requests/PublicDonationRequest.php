<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicDonationRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private array $allowedFields = [
        'organization',
        'campaign_slug',
        'donor_name',
        'donor_email',
        'amount',
        'currency',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
        }
    }

    public function rules(): array
    {
        return [
            'organization' => ['nullable', 'alpha_dash', 'max:120'],
            'campaign_slug' => ['nullable', 'alpha_dash', 'max:255'],
            'donor_name' => ['nullable', 'string', 'max:120'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unexpected = collect(array_keys($this->all()))
                ->diff($this->allowedFields)
                ->values();

            if ($unexpected->isNotEmpty()) {
                $validator->errors()->add('payload', 'Unexpected fields: '.$unexpected->implode(', '));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return collect($this->validated())
            ->except('organization')
            ->all();
    }
}
