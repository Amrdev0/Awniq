<?php

namespace App\Services;

use App\Models\Donation;

class DonationNumberGenerator
{
    public function generate(int $organizationId): string
    {
        $nextNumber = Donation::query()
            ->where('organization_id', $organizationId)
            ->withTrashed()
            ->count() + 1;

        do {
            $donationNumber = 'DON-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            Donation::query()
                ->where('organization_id', $organizationId)
                ->where('donation_number', $donationNumber)
                ->withTrashed()
                ->exists()
        );

        return $donationNumber;
    }
}
