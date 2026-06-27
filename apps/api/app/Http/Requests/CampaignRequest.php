<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $campaignId = $this->route('campaign')?->id;
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => [$requiredOnCreate, 'string', 'max:255'],
            'slug' => [
                $requiredOnCreate,
                'alpha_dash',
                'max:255',
                Rule::unique('campaigns', 'slug')->where('organization_id', $organizationId)->ignore($campaignId),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'goal_amount' => [$requiredOnCreate, 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => [$requiredOnCreate, 'string', 'size:3'],
            'start_date' => [$requiredOnCreate, 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:draft,active,paused,completed,cancelled'],
            'visibility' => ['nullable', 'in:private,public'],
            'cover_image' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
