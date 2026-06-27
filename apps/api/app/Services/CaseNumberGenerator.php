<?php

namespace App\Services;

use App\Models\CaseFile;

class CaseNumberGenerator
{
    public function generate(int $organizationId): string
    {
        $nextNumber = CaseFile::query()
            ->where('organization_id', $organizationId)
            ->withTrashed()
            ->count() + 1;

        do {
            $caseNumber = 'CASE-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            CaseFile::query()
                ->where('organization_id', $organizationId)
                ->where('case_number', $caseNumber)
                ->withTrashed()
                ->exists()
        );

        return $caseNumber;
    }
}
