<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $itemId = $this->route('inventoryItem')?->id;
        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'sku' => [
                $requiredOnCreate,
                'alpha_dash',
                'max:80',
                Rule::unique('inventory_items', 'sku')->where('organization_id', $organizationId)->ignore($itemId),
            ],
            'name' => [$requiredOnCreate, 'string', 'max:255'],
            'category' => [$requiredOnCreate, 'string', 'max:120'],
            'unit' => [$requiredOnCreate, 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0', 'max:9999999999.999'],
            'track_expiry' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
