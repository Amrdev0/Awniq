<?php

namespace App\Services;

use App\Models\Beneficiary;

class BeneficiaryCodeGenerator
{
    public function generate(int $organizationId): string
    {
        $nextNumber = Beneficiary::query()
            ->where('organization_id', $organizationId)
            ->withTrashed()
            ->count() + 1;

        do {
            $code = 'BEN-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            Beneficiary::query()
                ->where('organization_id', $organizationId)
                ->where('code', $code)
                ->withTrashed()
                ->exists()
        );

        return $code;
    }
}
