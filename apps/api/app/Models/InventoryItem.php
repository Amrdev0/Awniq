<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'sku',
        'name',
        'category',
        'unit',
        'description',
        'minimum_stock_level',
        'track_expiry',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock_level' => 'decimal:3',
            'track_expiry' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function stockLots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
