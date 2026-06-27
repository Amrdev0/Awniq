<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonorRequest;
use App\Http\Resources\DonationResource;
use App\Http\Resources\DonorResource;
use App\Models\Donor;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DonorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $donors = Donor::query()
            ->withCount('donations')
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('donor_type'), fn ($query, string $type) => $query->where('donor_type', $type))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return DonorResource::collection($donors);
    }

    public function store(DonorRequest $request, AuditLogService $auditLogService): DonorResource
    {
        $validated = $request->validated();
        $donor = Donor::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'name' => $validated['donor_type'] === 'anonymous' ? ($validated['name'] ?? 'Anonymous Donor') : $validated['name'],
            'status' => $validated['status'] ?? 'active',
        ]);

        $auditLogService->record('donor.created', $donor, null, $donor->toArray(), $request);

        return new DonorResource($donor->loadCount('donations'));
    }

    public function show(Donor $donor): DonorResource
    {
        $this->assertDonorScope($donor);

        return new DonorResource($donor->load(['donations.campaign', 'donations.receipt'])->loadCount('donations'));
    }

    public function update(DonorRequest $request, Donor $donor, AuditLogService $auditLogService): DonorResource
    {
        $this->assertDonorScope($donor);

        $oldValues = $donor->toArray();
        $donor->update($request->validated());

        $auditLogService->record('donor.updated', $donor, $oldValues, $donor->fresh()->toArray(), $request);

        return new DonorResource($donor->fresh()->loadCount('donations'));
    }

    public function destroy(Donor $donor, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertDonorScope($donor);

        $oldValues = $donor->toArray();
        $donor->delete();

        $auditLogService->record('donor.deleted', $donor, $oldValues, null, request());

        return ApiResponse::success(message: 'Donor deleted successfully.');
    }

    public function donations(Donor $donor): AnonymousResourceCollection
    {
        $this->assertDonorScope($donor);

        $donations = $donor->donations()
            ->with(['campaign', 'allocations', 'receipt'])
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return DonationResource::collection($donations);
    }

    private function assertDonorScope(Donor $donor): void
    {
        abort_unless($donor->organization_id === request()->user()->organization_id, 404);
    }
}
