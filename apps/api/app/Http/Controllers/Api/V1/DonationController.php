<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmDonationRequest;
use App\Http\Requests\DonationRequest;
use App\Http\Resources\DonationResource;
use App\Http\Resources\ReceiptResource;
use App\Models\Donation;
use App\Models\Receipt;
use App\Services\AuditLogService;
use App\Services\DonationAllocationService;
use App\Services\DonationConfirmationService;
use App\Services\DonationNumberGenerator;
use App\Services\IdempotencyService;
use App\Services\Notifications\NotificationService;
use App\Services\ReceiptNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $donations = Donation::query()
            ->with(['donor', 'campaign', 'receipt'])
            ->withCount(['allocations', 'paymentTransactions'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('donor_id'), fn ($query, string $donorId) => $query->where('donor_id', $donorId))
            ->when($request->query('campaign_id'), fn ($query, string $campaignId) => $query->where('campaign_id', $campaignId))
            ->when($request->query('payment_status'), fn ($query, string $status) => $query->where('payment_status', $status))
            ->when($request->query('donation_status'), fn ($query, string $status) => $query->where('donation_status', $status))
            ->when($request->query('payment_method'), fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($request->query('date_from'), fn ($query, string $date) => $query->whereDate('donated_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, string $date) => $query->whereDate('donated_at', '<=', $date))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('donation_number', 'like', "%{$search}%")
                    ->orWhereHas('donor', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return DonationResource::collection($donations);
    }

    public function store(
        DonationRequest $request,
        DonationNumberGenerator $donationNumberGenerator,
        DonationAllocationService $allocationService,
        AuditLogService $auditLogService,
    ): DonationResource {
        $validated = $request->validated();
        $allocations = $validated['allocations'] ?? [];
        unset($validated['allocations']);

        $donation = DB::transaction(function () use ($request, $validated, $allocations, $donationNumberGenerator, $allocationService): Donation {
            $donation = Donation::create([
                ...$validated,
                'organization_id' => $request->user()->organization_id,
                'donation_number' => $donationNumberGenerator->generate($request->user()->organization_id),
                'payment_status' => 'pending',
                'donation_status' => $validated['donation_status'] ?? 'pending',
                'created_by' => $request->user()->id,
            ]);

            foreach ($allocations as $allocation) {
                $allocationService->create($donation, $allocation);
            }

            return $donation->fresh();
        });

        $auditLogService->record('donation.created', $donation, null, $donation->load('allocations')->toArray(), $request);

        return new DonationResource($donation->load(['donor', 'campaign', 'allocations.campaign', 'allocations.beneficiary', 'allocations.caseFile', 'paymentTransactions', 'receipt'])->loadCount(['allocations', 'paymentTransactions']));
    }

    public function show(Donation $donation): DonationResource
    {
        $this->assertDonationScope($donation);

        return new DonationResource($donation->load(['donor', 'campaign', 'creator', 'allocations.campaign', 'allocations.beneficiary', 'allocations.caseFile', 'paymentTransactions', 'receipt.issuer'])->loadCount(['allocations', 'paymentTransactions']));
    }

    public function update(DonationRequest $request, Donation $donation, DonationAllocationService $allocationService, AuditLogService $auditLogService): DonationResource
    {
        $this->assertDonationScope($donation);

        $validated = $request->validated();
        $allocations = $validated['allocations'] ?? null;
        unset($validated['allocations'], $validated['payment_status'], $validated['donation_status']);

        if ($donation->isConfirmed()) {
            abort_unless(array_keys($validated) === ['notes'] || $validated === [], 422, 'Confirmed donations can only update notes.');
        } else {
            $allocationService->assertDonationIsMutable($donation);
        }

        $oldValues = $donation->load('allocations')->toArray();

        DB::transaction(function () use ($donation, $validated, $allocations, $allocationService): void {
            $donation->update($validated);

            if (is_array($allocations)) {
                $donation->allocations()->delete();

                foreach ($allocations as $allocation) {
                    $allocationService->create($donation->fresh(), $allocation);
                }
            }
        });

        $auditLogService->record('donation.updated', $donation, $oldValues, $donation->fresh()->load('allocations')->toArray(), $request);

        return new DonationResource($donation->fresh()->load(['donor', 'campaign', 'allocations.campaign', 'allocations.beneficiary', 'allocations.caseFile', 'paymentTransactions', 'receipt'])->loadCount(['allocations', 'paymentTransactions']));
    }

    public function confirm(
        ConfirmDonationRequest $request,
        Donation $donation,
        DonationConfirmationService $confirmationService,
        IdempotencyService $idempotencyService,
        AuditLogService $auditLogService,
        NotificationService $notifications,
    ): JsonResponse {
        $this->assertDonationScope($donation);

        if ($storedResponse = $idempotencyService->storedResponse($request)) {
            return $storedResponse;
        }

        $oldValues = $donation->toArray();
        $confirmedDonation = $confirmationService->confirm($donation, $request->user(), $request->validated(), $request->header('Idempotency-Key'));

        $auditLogService->record('donation.confirmed', $confirmedDonation, $oldValues, $confirmedDonation->toArray(), $request);
        $notifications->donationConfirmed($confirmedDonation);

        $body = [
            'data' => (new DonationResource($confirmedDonation))->resolve($request),
            'message' => 'Donation confirmed successfully.',
        ];

        $idempotencyService->rememberResponse($request, 200, $body);

        return response()->json($body);
    }

    public function cancel(Donation $donation, AuditLogService $auditLogService): DonationResource
    {
        $this->assertDonationScope($donation);
        abort_if($donation->isConfirmed(), 422, 'Confirmed donations cannot be cancelled.');
        abort_if(in_array($donation->donation_status, ['cancelled', 'refunded'], true), 422, 'This donation is already closed.');

        $oldValues = $donation->only(['payment_status', 'donation_status']);
        $donation->update([
            'payment_status' => 'cancelled',
            'donation_status' => 'cancelled',
        ]);

        $auditLogService->record('donation.cancelled', $donation, $oldValues, $donation->fresh()->only(['payment_status', 'donation_status']), request());

        return new DonationResource($donation->fresh()->load(['donor', 'campaign', 'allocations', 'paymentTransactions', 'receipt']));
    }

    public function refund(Donation $donation): JsonResponse
    {
        $this->assertDonationScope($donation);

        abort(501, 'Refund workflow is not implemented yet.');
    }

    public function receipt(Donation $donation): ReceiptResource
    {
        $this->assertDonationScope($donation);

        $receipt = $donation->receipt()->with('issuer')->firstOrFail();

        return new ReceiptResource($receipt);
    }

    public function generateReceipt(
        Donation $donation,
        ReceiptNumberGenerator $receiptNumberGenerator,
        AuditLogService $auditLogService,
        NotificationService $notifications,
    ): ReceiptResource {
        $this->assertDonationScope($donation);
        abort_unless($donation->isConfirmed(), 422, 'Receipts can only be generated for confirmed donations.');

        $receipt = Receipt::firstOrCreate(
            ['donation_id' => $donation->id],
            [
                'organization_id' => $donation->organization_id,
                'receipt_number' => $receiptNumberGenerator->generate($donation->organization_id),
                'issued_at' => now(),
                'issued_by' => request()->user()->id,
                'status' => 'issued',
            ],
        );

        $auditLogService->record('receipt.generated', $receipt, null, $receipt->toArray(), request());
        $notifications->receiptGenerated($receipt->load('donation'));

        return new ReceiptResource($receipt->load('issuer'));
    }

    private function assertDonationScope(Donation $donation): void
    {
        abort_unless($donation->organization_id === request()->user()->organization_id, 404);
    }
}
