<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'description',
        'goal_amount',
        'collected_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
        'visibility',
        'cover_image',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'goal_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DonationAllocation::class);
    }
}
