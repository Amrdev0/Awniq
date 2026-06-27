<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
            'stock_lot_id' => ['nullable', Rule::exists('stock_lots', 'id')->where('organization_id', $organizationId)],
            'movement_type' => ['required', 'in:adjustment_in,adjustment_out,damaged,expired'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999999999.999'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['required', 'string', 'max:2000'],
        ];
    }
}
