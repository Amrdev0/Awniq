<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\CaseFile;
use Illuminate\Database\Eloquent\Builder;

class BeneficiaryEligibilityService
{
    public function eligibleQuery(int $organizationId, ?int $branchId = null): Builder
    {
        return Beneficiary::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'approved')
            ->when($branchId, fn (Builder $query): Builder => $query->where('branch_id', $branchId));
    }

    public function assertEligible(Beneficiary $beneficiary): void
    {
        abort_unless($beneficiary->status === 'approved', 422, 'Beneficiary must be approved and active before aid distribution.');
    }

    public function assertCaseBelongsToBeneficiary(?CaseFile $caseFile, Beneficiary $beneficiary): void
    {
        if (! $caseFile) {
            return;
        }

        abort_unless($caseFile->organization_id === $beneficiary->organization_id, 404);
        abort_unless($caseFile->beneficiary_id === $beneficiary->id, 422, 'Case file must belong to the selected beneficiary.');
    }
}
