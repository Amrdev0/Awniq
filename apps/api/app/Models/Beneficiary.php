<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'code',
        'full_name',
        'national_id',
        'birth_date',
        'gender',
        'phone',
        'alternate_phone',
        'email',
        'country',
        'city',
        'district',
        'address',
        'marital_status',
        'employment_status',
        'monthly_income',
        'household_size',
        'vulnerability_level',
        'status',
        'created_by',
        'reviewed_by_user_id',
        'approved_by_user_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'monthly_income' => 'decimal:2',
            'household_size' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(BeneficiaryFamilyMember::class);
    }

    public function caseFiles(): HasMany
    {
        return $this->hasMany(CaseFile::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class);
    }
}
