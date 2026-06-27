<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'key',
        'method',
        'route',
        'request_hash',
        'response_code',
        'response_body',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'locked_until' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
