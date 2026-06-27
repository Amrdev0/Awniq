<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BeneficiaryRequest;
use App\Http\Requests\RejectionReasonRequest;
use App\Http\Resources\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Services\AuditLogService;
use App\Services\BeneficiaryCodeGenerator;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BeneficiaryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $beneficiaries = Beneficiary::query()
            ->with(['branch', 'creator'])
            ->withCount(['familyMembers', 'caseFiles'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('branch_id'), fn ($query, string $branchId) => $query->where('branch_id', $branchId))
            ->when($request->query('vulnerability_level'), fn ($query, string $level) => $query->where('vulnerability_level', $level))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return BeneficiaryResource::collection($beneficiaries);
    }

    public function store(BeneficiaryRequest $request, BeneficiaryCodeGenerator $codeGenerator, AuditLogService $auditLogService): BeneficiaryResource
    {
        $validated = $request->validated();

        $beneficiary = Beneficiary::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'code' => $codeGenerator->generate($request->user()->organization_id),
            'status' => $validated['status'] ?? 'draft',
            'created_by' => $request->user()->id,
        ]);

        $auditLogService->record('beneficiary.created', $beneficiary, null, $beneficiary->toArray(), $request);

        return new BeneficiaryResource($beneficiary->load(['branch', 'creator'])->loadCount(['familyMembers', 'caseFiles']));
    }

    public function show(Beneficiary $beneficiary): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);

        return new BeneficiaryResource($beneficiary->load([
            'branch',
            'creator',
            'reviewedBy',
            'approvedBy',
            'familyMembers',
            'caseFiles.assignedTo',
        ])->loadCount(['familyMembers', 'caseFiles']));
    }

    public function update(BeneficiaryRequest $request, Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);

        $validated = $request->validated();
        $oldValues = $beneficiary->toArray();

        $beneficiary->update($validated);

        $auditLogService->record('beneficiary.updated', $beneficiary, $oldValues, $beneficiary->fresh()->toArray(), $request);

        return new BeneficiaryResource($beneficiary->fresh()->load(['branch', 'creator'])->loadCount(['familyMembers', 'caseFiles']));
    }

    public function destroy(Beneficiary $beneficiary, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertBeneficiaryScope($beneficiary);

        $oldValues = $beneficiary->toArray();
        $beneficiary->delete();

        $auditLogService->record('beneficiary.deleted', $beneficiary, $oldValues, null, request());

        return ApiResponse::success(message: 'Beneficiary deleted successfully.');
    }

    public function submitReview(Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);
        $this->assertStatusIn($beneficiary, ['draft', 'rejected'], 'Only draft or rejected beneficiaries can be submitted for review.');

        return $this->transition($beneficiary, 'pending_review', 'beneficiary.submitted_for_review', [
            'reviewed_by_user_id' => null,
            'approved_by_user_id' => null,
            'rejection_reason' => null,
        ], $auditLogService);
    }

    public function approve(Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);
        $this->assertStatusIn($beneficiary, ['pending_review'], 'Only beneficiaries pending review can be approved.');

        return $this->transition($beneficiary, 'approved', 'beneficiary.approved', [
            'reviewed_by_user_id' => request()->user()->id,
            'approved_by_user_id' => request()->user()->id,
            'rejection_reason' => null,
        ], $auditLogService);
    }

    public function reject(RejectionReasonRequest $request, Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);
        $this->assertStatusIn($beneficiary, ['pending_review'], 'Only beneficiaries pending review can be rejected.');

        return $this->transition($beneficiary, 'rejected', 'beneficiary.rejected', [
            'reviewed_by_user_id' => $request->user()->id,
            'approved_by_user_id' => null,
            'rejection_reason' => $request->validated('reason'),
        ], $auditLogService, $request);
    }

    public function suspend(Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);
        $this->assertStatusIn($beneficiary, ['draft', 'pending_review', 'approved', 'rejected'], 'This beneficiary cannot be suspended.');

        return $this->transition($beneficiary, 'suspended', 'beneficiary.suspended', [], $auditLogService);
    }

    public function reactivate(Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryResource
    {
        $this->assertBeneficiaryScope($beneficiary);
        $this->assertStatusIn($beneficiary, ['suspended', 'archived'], 'Only suspended or archived beneficiaries can be reactivated.');

        return $this->transition($beneficiary, 'draft', 'beneficiary.reactivated', [
            'reviewed_by_user_id' => null,
            'approved_by_user_id' => null,
            'rejection_reason' => null,
        ], $auditLogService);
    }

    public function duplicateCandidates(Beneficiary $beneficiary): AnonymousResourceCollection
    {
        $this->assertBeneficiaryScope($beneficiary);

        $candidates = Beneficiary::query()
            ->with('branch')
            ->where('organization_id', $beneficiary->organization_id)
            ->whereKeyNot($beneficiary->id)
            ->where(function ($query) use ($beneficiary): void {
                if ($beneficiary->national_id) {
                    $query->orWhere('national_id', $beneficiary->national_id);
                }

                if ($beneficiary->phone) {
                    $query->orWhere('phone', $beneficiary->phone);
                }

                $query->orWhere('full_name', 'like', "%{$beneficiary->full_name}%");
            })
            ->limit(10)
            ->get();

        return BeneficiaryResource::collection($candidates);
    }

    /**
     * @param  list<string>  $allowedStatuses
     */
    private function assertStatusIn(Beneficiary $beneficiary, array $allowedStatuses, string $message): void
    {
        abort_unless(in_array($beneficiary->status, $allowedStatuses, true), 422, $message);
    }

    /**
     * @param  array<string, mixed>  $extraValues
     */
    private function transition(
        Beneficiary $beneficiary,
        string $status,
        string $action,
        array $extraValues,
        AuditLogService $auditLogService,
        ?Request $request = null,
    ): BeneficiaryResource {
        $request ??= request();
        $oldValues = $beneficiary->only(['status', 'reviewed_by_user_id', 'approved_by_user_id', 'rejection_reason']);

        $beneficiary->update([
            ...$extraValues,
            'status' => $status,
        ]);

        $auditLogService->record(
            $action,
            $beneficiary,
            $oldValues,
            $beneficiary->fresh()->only(['status', 'reviewed_by_user_id', 'approved_by_user_id', 'rejection_reason']),
            $request,
        );

        return new BeneficiaryResource($beneficiary->fresh()->load(['branch', 'creator', 'reviewedBy', 'approvedBy'])->loadCount(['familyMembers', 'caseFiles']));
    }

    private function assertBeneficiaryScope(Beneficiary $beneficiary): void
    {
        abort_unless($beneficiary->organization_id === request()->user()->organization_id, 404);
    }
}
