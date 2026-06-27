<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('organization_id', $organizationId)],
            'inventory_item_id' => ['required', Rule::exists('inventory_items', 'id')->where('organization_id', $organizationId)],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999999999.999'],
            'source_type' => ['required', 'in:opening_balance,purchase,donation_in_kind,transfer,adjustment,other'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'expiry_date' => ['nullable', 'date'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
