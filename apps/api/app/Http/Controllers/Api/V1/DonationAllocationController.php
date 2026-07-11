<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonationAllocationRequest;
use App\Http\Resources\DonationAllocationResource;
use App\Models\Donation;
use App\Models\DonationAllocation;
use App\Services\AuditLogService;
use App\Services\DonationAllocationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DonationAllocationController extends Controller
{
    public function index(Donation $donation): AnonymousResourceCollection
    {
        $this->assertDonationScope($donation);

        $allocations = $donation->allocations()
            ->with(['campaign', 'beneficiary', 'caseFile'])
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('allocation_type', 'like', "%{$search}%")->orWhere('notes', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return DonationAllocationResource::collection($allocations);
    }

    public function store(DonationAllocationRequest $request, Donation $donation, DonationAllocationService $allocationService, AuditLogService $auditLogService): DonationAllocationResource
    {
        $this->assertDonationScope($donation);

        $allocation = $allocationService->create($donation, $request->validated());

        $auditLogService->record('donation_allocation.created', $allocation, null, $allocation->toArray(), $request);

        return new DonationAllocationResource($allocation->load(['campaign', 'beneficiary', 'caseFile']));
    }

    public function update(
        DonationAllocationRequest $request,
        Donation $donation,
        DonationAllocation $allocation,
        DonationAllocationService $allocationService,
        AuditLogService $auditLogService,
    ): DonationAllocationResource {
        $this->assertAllocationScope($donation, $allocation);

        $oldValues = $allocation->toArray();
        $updatedAllocation = $allocationService->update($donation, $allocation, $request->validated());

        $auditLogService->record('donation_allocation.updated', $updatedAllocation, $oldValues, $updatedAllocation->toArray(), $request);

        return new DonationAllocationResource($updatedAllocation->load(['campaign', 'beneficiary', 'caseFile']));
    }

    public function destroy(Donation $donation, DonationAllocation $allocation, DonationAllocationService $allocationService, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertAllocationScope($donation, $allocation);

        $oldValues = $allocation->toArray();
        $allocationService->delete($donation, $allocation);

        $auditLogService->record('donation_allocation.deleted', $allocation, $oldValues, null, request());

        return ApiResponse::success(message: 'Donation allocation deleted successfully.');
    }

    private function assertDonationScope(Donation $donation): void
    {
        abort_unless($donation->organization_id === request()->user()->organization_id, 404);
    }

    private function assertAllocationScope(Donation $donation, DonationAllocation $allocation): void
    {
        $this->assertDonationScope($donation);
        abort_unless($allocation->donation_id === $donation->id, 404);
        abort_unless($allocation->organization_id === request()->user()->organization_id, 404);
    }
}
