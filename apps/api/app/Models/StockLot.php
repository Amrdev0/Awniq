<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLot extends Model
{
    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'inventory_item_id',
        'source_type',
        'source_id',
        'quantity',
        'remaining_quantity',
        'reserved_quantity',
        'expiry_date',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'expiry_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
