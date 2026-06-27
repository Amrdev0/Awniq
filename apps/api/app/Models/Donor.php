<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'donor_type',
        'name',
        'email',
        'phone',
        'country',
        'city',
        'address',
        'tax_number',
        'notes',
        'communication_preferences',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'communication_preferences' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
