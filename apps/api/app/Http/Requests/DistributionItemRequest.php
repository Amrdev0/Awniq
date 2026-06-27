<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistributionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'inventory_item_id' => ['nullable', Rule::exists('inventory_items', 'id')->where('organization_id', $organizationId)],
            'stock_lot_id' => ['nullable', Rule::exists('stock_lots', 'id')->where('organization_id', $organizationId)],
            'quantity' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.999'],
            'cash_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasInventoryAid = $this->filled('inventory_item_id') && $this->filled('quantity');
            $hasCashAid = $this->filled('cash_amount') && $this->filled('currency');

            if (! $hasInventoryAid && ! $hasCashAid) {
                $validator->errors()->add('items', 'Provide either inventory item quantity or cash amount and currency.');
            }

            if ($this->filled('stock_lot_id') && ! $this->filled('inventory_item_id')) {
                $validator->errors()->add('inventory_item_id', 'Inventory item is required when a stock lot is supplied.');
            }

            if ($this->filled('inventory_item_id') && ! $this->filled('quantity')) {
                $validator->errors()->add('quantity', 'Quantity is required when an inventory item is supplied.');
            }

            if ($this->filled('cash_amount') && ! $this->filled('currency')) {
                $validator->errors()->add('currency', 'Currency is required when cash amount is supplied.');
            }
        });
    }
}
