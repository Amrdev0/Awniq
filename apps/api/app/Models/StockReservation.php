<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    protected $fillable = [
        'organization_id',
        'aid_batch_id',
        'aid_distribution_id',
        'distribution_item_id',
        'stock_lot_id',
        'quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function aidBatch(): BelongsTo
    {
        return $this->belongsTo(AidBatch::class);
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(AidDistribution::class, 'aid_distribution_id');
    }

    public function distributionItem(): BelongsTo
    {
        return $this->belongsTo(DistributionItem::class);
    }

    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }
}
