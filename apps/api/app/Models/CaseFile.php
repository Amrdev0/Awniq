<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'beneficiary_id',
        'case_number',
        'case_type',
        'priority',
        'status',
        'assigned_to_user_id',
        'reviewed_by_user_id',
        'approved_by_user_id',
        'rejection_reason',
        'assessment_summary',
        'next_follow_up_date',
    ];

    protected function casts(): array
    {
        return [
            'next_follow_up_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class);
    }
}
