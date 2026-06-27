<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\CaseFile;
use App\Models\Donation;
use App\Models\DonationAllocation;

class DonationAllocationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Donation $donation, array $payload): DonationAllocation
    {
        $this->assertDonationIsMutable($donation);
        $this->assertValidTargets($payload);

        return $donation->allocations()->create([
            ...$payload,
            'organization_id' => $donation->organization_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Donation $donation, DonationAllocation $allocation, array $payload): DonationAllocation
    {
        $this->assertDonationIsMutable($donation);
        $this->assertValidTargets([
            ...$allocation->toArray(),
            ...$payload,
        ]);

        $allocation->update($payload);

        return $allocation->fresh();
    }

    public function delete(Donation $donation, DonationAllocation $allocation): void
    {
        $this->assertDonationIsMutable($donation);
        $allocation->delete();
    }

    public function assertDonationIsMutable(Donation $donation): void
    {
        abort_if($donation->isConfirmed(), 422, 'Confirmed donations cannot be changed.');
        abort_if(in_array($donation->donation_status, ['cancelled', 'refunded'], true), 422, 'Cancelled or refunded donations cannot be changed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assertValidTargets(array $payload): void
    {
        $type = $payload['allocation_type'] ?? null;

        if ($type === 'campaign') {
            abort_unless((bool) ($payload['campaign_id'] ?? null), 422, 'Campaign allocation requires a campaign.');

            $campaign = Campaign::findOrFail($payload['campaign_id']);
            abort_unless($campaign->status === 'active', 422, 'Only active campaigns can receive campaign allocations.');
        }

        if ($type === 'beneficiary') {
            abort_unless((bool) ($payload['beneficiary_id'] ?? null), 422, 'Beneficiary allocation requires a beneficiary.');

            $beneficiary = Beneficiary::findOrFail($payload['beneficiary_id']);
            abort_unless($beneficiary->status === 'approved', 422, 'Only approved beneficiaries can receive beneficiary allocations.');
        }

        if ($type === 'case_file') {
            abort_unless((bool) ($payload['case_file_id'] ?? null), 422, 'Case file allocation requires a case file.');

            $caseFile = CaseFile::findOrFail($payload['case_file_id']);
            abort_unless($caseFile->status === 'approved', 422, 'Only approved case files can receive case allocations.');
        }
    }
}
