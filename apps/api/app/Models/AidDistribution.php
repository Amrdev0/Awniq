<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AidDistribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'aid_batch_id',
        'beneficiary_id',
        'case_file_id',
        'distribution_number',
        'status',
        'scheduled_at',
        'delivered_at',
        'delivered_by',
        'delivery_method',
        'proof_type',
        'proof_file_path',
        'beneficiary_signature_path',
        'otp_code',
        'failure_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function aidBatch(): BelongsTo
    {
        return $this->belongsTo(AidBatch::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DistributionItem::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }
}
