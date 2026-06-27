<?php

namespace App\Services;

use App\Models\AidBatch;

class AidBatchNumberGenerator
{
    public function generate(int $organizationId): string
    {
        $nextNumber = AidBatch::query()
            ->where('organization_id', $organizationId)
            ->withTrashed()
            ->count() + 1;

        do {
            $batchNumber = 'AID-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            AidBatch::query()
                ->where('organization_id', $organizationId)
                ->where('batch_number', $batchNumber)
                ->withTrashed()
                ->exists()
        );

        return $batchNumber;
    }
}
