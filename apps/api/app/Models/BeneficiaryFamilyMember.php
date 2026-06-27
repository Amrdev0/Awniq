<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryFamilyMember extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'full_name',
        'relationship',
        'birth_date',
        'gender',
        'national_id',
        'education_level',
        'employment_status',
        'health_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
