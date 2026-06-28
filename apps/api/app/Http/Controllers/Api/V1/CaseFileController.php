<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CaseFileRequest;
use App\Http\Requests\RejectionReasonRequest;
use App\Http\Resources\CaseFileResource;
use App\Models\Beneficiary;
use App\Models\CaseFile;
use App\Services\AuditLogService;
use App\Services\CaseNumberGenerator;
use App\Services\Notifications\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CaseFileController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $caseFiles = CaseFile::query()
            ->with(['beneficiary.branch', 'assignedTo'])
            ->withCount(['notes', 'documents'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('priority'), fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($request->query('beneficiary_id'), fn ($query, string $beneficiaryId) => $query->where('beneficiary_id', $beneficiaryId))
            ->when($request->query('assigned_to_user_id'), fn ($query, string $userId) => $query->where('assigned_to_user_id', $userId))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('case_number', 'like', "%{$search}%")
                    ->orWhereHas('beneficiary', function ($query) use ($search): void {
                        $query
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%");
                    });
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return CaseFileResource::collection($caseFiles);
    }

    public function store(CaseFileRequest $request, CaseNumberGenerator $caseNumberGenerator, AuditLogService $auditLogService): CaseFileResource
    {
        $validated = $request->validated();
        $beneficiary = Beneficiary::findOrFail($validated['beneficiary_id']);
        $this->assertBeneficiaryScope($beneficiary);

        $caseFile = CaseFile::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'case_number' => $caseNumberGenerator->generate($request->user()->organization_id),
            'status' => $validated['status'] ?? 'open',
        ]);

        $auditLogService->record('case_file.created', $caseFile, null, $caseFile->toArray(), $request);

        return new CaseFileResource($caseFile->load(['beneficiary.branch', 'assignedTo'])->loadCount(['notes', 'documents']));
    }

    public function show(CaseFile $caseFile): CaseFileResource
    {
        $this->assertCaseScope($caseFile);

        return new CaseFileResource($caseFile->load([
            'beneficiary.branch',
            'assignedTo',
            'reviewedBy',
            'approvedBy',
            'notes.user',
            'documents.uploader',
        ])->loadCount(['notes', 'documents']));
    }

    public function update(CaseFileRequest $request, CaseFile $caseFile, AuditLogService $auditLogService): CaseFileResource
    {
        $this->assertCaseScope($caseFile);

        $validated = $request->validated();
        if (isset($validated['beneficiary_id'])) {
            $beneficiary = Beneficiary::findOrFail($validated['beneficiary_id']);
            $this->assertBeneficiaryScope($beneficiary);
        }

        $oldValues = $caseFile->toArray();
        $caseFile->update($validated);

        $auditLogService->record('case_file.updated', $caseFile, $oldValues, $caseFile->fresh()->toArray(), $request);

        return new CaseFileResource($caseFile->fresh()->load(['beneficiary.branch', 'assignedTo'])->loadCount(['notes', 'documents']));
    }

    public function destroy(CaseFile $caseFile, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertCaseScope($caseFile);

        $oldValues = $caseFile->toArray();
        $caseFile->delete();

        $auditLogService->record('case_file.deleted', $caseFile, $oldValues, null, request());

        return ApiResponse::success(message: 'Case file deleted successfully.');
    }

    public function submitReview(CaseFile $caseFile, AuditLogService $auditLogService, NotificationService $notifications): CaseFileResource
    {
        $this->assertCaseScope($caseFile);
        $this->assertStatusIn($caseFile, ['open', 'rejected'], 'Only open or rejected cases can be submitted for review.');

        $resource = $this->transition($caseFile, 'under_review', 'case_file.submitted_for_review', [
            'reviewed_by_user_id' => null,
            'approved_by_user_id' => null,
            'rejection_reason' => null,
        ], $auditLogService);

        $notifications->caseSubmittedForReview($caseFile->fresh()->load(['beneficiary', 'assignedTo']));

        return $resource;
    }

    public function approve(CaseFile $caseFile, AuditLogService $auditLogService, NotificationService $notifications): CaseFileResource
    {
        $this->assertCaseScope($caseFile);
        $this->assertStatusIn($caseFile, ['under_review'], 'Only cases under review can be approved.');

        $resource = $this->transition($caseFile, 'approved', 'case_file.approved', [
            'reviewed_by_user_id' => request()->user()->id,
            'approved_by_user_id' => request()->user()->id,
            'rejection_reason' => null,
        ], $auditLogService);

        $notifications->caseDecision($caseFile->fresh()->load(['beneficiary', 'assignedTo']), 'approved');

        return $resource;
    }

    public function reject(RejectionReasonRequest $request, CaseFile $caseFile, AuditLogService $auditLogService, NotificationService $notifications): CaseFileResource
    {
        $this->assertCaseScope($caseFile);
        $this->assertStatusIn($caseFile, ['under_review'], 'Only cases under review can be rejected.');

        $resource = $this->transition($caseFile, 'rejected', 'case_file.rejected', [
            'reviewed_by_user_id' => $request->user()->id,
            'approved_by_user_id' => null,
            'rejection_reason' => $request->validated('reason'),
        ], $auditLogService, $request);

        $notifications->caseDecision($caseFile->fresh()->load(['beneficiary', 'assignedTo']), 'rejected');

        return $resource;
    }

    public function suspend(CaseFile $caseFile, AuditLogService $auditLogService): CaseFileResource
    {
        $this->assertCaseScope($caseFile);
        $this->assertStatusIn($caseFile, ['open', 'under_review', 'approved', 'rejected'], 'This case cannot be suspended.');

        return $this->transition($caseFile, 'suspended', 'case_file.suspended', [], $auditLogService);
    }

    public function close(CaseFile $caseFile, AuditLogService $auditLogService): CaseFileResource
    {
        $this->assertCaseScope($caseFile);
        $this->assertStatusIn($caseFile, ['open', 'under_review', 'approved', 'rejected', 'suspended'], 'This case cannot be closed.');

        return $this->transition($caseFile, 'closed', 'case_file.closed', [], $auditLogService);
    }

    public function reopen(CaseFile $caseFile, AuditLogService $auditLogService): CaseFileResource
    {
        $this->assertCaseScope($caseFile);
        $this->assertStatusIn($caseFile, ['closed', 'rejected', 'suspended'], 'Only closed, rejected, or suspended cases can be reopened.');

        return $this->transition($caseFile, 'open', 'case_file.reopened', [
            'reviewed_by_user_id' => null,
            'approved_by_user_id' => null,
            'rejection_reason' => null,
        ], $auditLogService);
    }

    /**
     * @param  list<string>  $allowedStatuses
     */
    private function assertStatusIn(CaseFile $caseFile, array $allowedStatuses, string $message): void
    {
        abort_unless(in_array($caseFile->status, $allowedStatuses, true), 422, $message);
    }

    /**
     * @param  array<string, mixed>  $extraValues
     */
    private function transition(
        CaseFile $caseFile,
        string $status,
        string $action,
        array $extraValues,
        AuditLogService $auditLogService,
        ?Request $request = null,
    ): CaseFileResource {
        $request ??= request();
        $oldValues = $caseFile->only(['status', 'reviewed_by_user_id', 'approved_by_user_id', 'rejection_reason']);

        $caseFile->update([
            ...$extraValues,
            'status' => $status,
        ]);

        $auditLogService->record(
            $action,
            $caseFile,
            $oldValues,
            $caseFile->fresh()->only(['status', 'reviewed_by_user_id', 'approved_by_user_id', 'rejection_reason']),
            $request,
        );

        return new CaseFileResource($caseFile->fresh()->load(['beneficiary.branch', 'assignedTo', 'reviewedBy', 'approvedBy'])->loadCount(['notes', 'documents']));
    }

    private function assertCaseScope(CaseFile $caseFile): void
    {
        abort_unless($caseFile->organization_id === request()->user()->organization_id, 404);
    }

    private function assertBeneficiaryScope(Beneficiary $beneficiary): void
    {
        abort_unless($beneficiary->organization_id === request()->user()->organization_id, 404);
    }
}
