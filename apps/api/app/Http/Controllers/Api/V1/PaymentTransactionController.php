<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentTransactionResource;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentTransactionController extends Controller
{
    public function donationIndex(Donation $donation): AnonymousResourceCollection
    {
        $this->assertDonationScope($donation);

        $transactions = $donation->paymentTransactions()
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('provider', 'like', "%{$search}%")->orWhere('provider_transaction_id', 'like', "%{$search}%")->orWhere('status', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return PaymentTransactionResource::collection($transactions);
    }

    public function show(PaymentTransaction $paymentTransaction): PaymentTransactionResource
    {
        abort_unless($paymentTransaction->organization_id === request()->user()->organization_id, 404);

        return new PaymentTransactionResource($paymentTransaction);
    }

    private function assertDonationScope(Donation $donation): void
    {
        abort_unless($donation->organization_id === request()->user()->organization_id, 404);
    }
}
