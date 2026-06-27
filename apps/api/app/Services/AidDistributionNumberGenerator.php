<?php

namespace App\Services;

use App\Models\AidDistribution;

class AidDistributionNumberGenerator
{
    public function generate(int $organizationId): string
    {
        $nextNumber = AidDistribution::query()
            ->where('organization_id', $organizationId)
            ->withTrashed()
            ->count() + 1;

        do {
            $distributionNumber = 'DIST-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            AidDistribution::query()
                ->where('organization_id', $organizationId)
                ->where('distribution_number', $distributionNumber)
                ->withTrashed()
                ->exists()
        );

        return $distributionNumber;
    }
}
