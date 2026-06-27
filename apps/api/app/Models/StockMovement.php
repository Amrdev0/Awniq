<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'inventory_item_id',
        'stock_lot_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'created_at' => 'datetime',
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

    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
