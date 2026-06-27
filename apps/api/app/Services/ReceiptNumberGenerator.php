<?php

namespace App\Services;

use App\Models\Receipt;

class ReceiptNumberGenerator
{
    public function generate(int $organizationId): string
    {
        $nextNumber = Receipt::query()
            ->where('organization_id', $organizationId)
            ->count() + 1;

        do {
            $receiptNumber = 'REC-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            Receipt::query()
                ->where('organization_id', $organizationId)
                ->where('receipt_number', $receiptNumber)
                ->exists()
        );

        return $receiptNumber;
    }
}
