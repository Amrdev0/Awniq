<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AidBatchRequest;
use App\Http\Requests\AidDistributionRequest;
use App\Http\Resources\AidBatchResource;
use App\Http\Resources\AidDistributionResource;
use App\Http\Resources\BeneficiaryResource;
use App\Models\AidBatch;
use App\Models\Beneficiary;
use App\Models\CaseFile;
use App\Services\AidBatchNumberGenerator;
use App\Services\AidBatchWorkflowService;
use App\Services\AidDistributionNumberGenerator;
use App\Services\AuditLogService;
use App\Services\BeneficiaryEligibilityService;
use App\Services\IdempotencyService;
use App\Services\StockReservationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AidBatchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $batches = AidBatch::query()
            ->with(['branch', 'warehouse', 'campaign', 'creator', 'approver'])
            ->withCount(['distributions', 'reservations'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('branch_id'), fn ($query, string $branchId) => $query->where('branch_id', $branchId))
            ->when($request->query('warehouse_id'), fn ($query, string $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->query('campaign_id'), fn ($query, string $campaignId) => $query->where('campaign_id', $campaignId))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('scheduled_date'), fn ($query, string $date) => $query->whereDate('scheduled_date', $date))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('batch_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return AidBatchResource::collection($batches);
    }

    public function store(AidBatchRequest $request, AidBatchNumberGenerator $numberGenerator, AuditLogService $auditLogService): AidBatchResource
    {
        $batch = AidBatch::create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization_id,
            'batch_number' => $numberGenerator->generate($request->user()->organization_id),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        $auditLogService->record('aid_batch.created', $batch, null, $batch->toArray(), $request);

        return new AidBatchResource($batch->load(['branch', 'warehouse', 'campaign', 'creator'])->loadCount(['distributions', 'reservations']));
    }

    public function show(AidBatch $aidBatch): AidBatchResource
    {
        $this->assertBatchScope($aidBatch);

        return new AidBatchResource($aidBatch->load(['branch', 'warehouse', 'campaign', 'creator', 'approver', 'distributions.beneficiary', 'distributions.caseFile', 'distributions.items.inventoryItem', 'distributions.reservations.stockLot'])->loadCount(['distributions', 'reservations']));
    }

    public function update(AidBatchRequest $request, AidBatch $aidBatch, AuditLogService $auditLogService): AidBatchResource
    {
        $this->assertBatchScope($aidBatch);
        $this->assertBatchMutable($aidBatch);

        $oldValues = $aidBatch->toArray();
        $aidBatch->update($request->validated());

        $auditLogService->record('aid_batch.updated', $aidBatch, $oldValues, $aidBatch->fresh()->toArray(), $request);

        return new AidBatchResource($aidBatch->fresh()->load(['branch', 'warehouse', 'campaign', 'creator'])->loadCount(['distributions', 'reservations']));
    }

    public function destroy(AidBatch $aidBatch, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertBatchScope($aidBatch);
        abort_unless($aidBatch->status === 'draft', 422, 'Only draft batches can be deleted.');
        abort_if($aidBatch->reservations()->exists(), 422, 'Batches with stock reservations cannot be deleted.');

        $oldValues = $aidBatch->toArray();
        $aidBatch->delete();

        $auditLogService->record('aid_batch.deleted', $aidBatch, $oldValues, null, request());

        return ApiResponse::success(message: 'Aid batch deleted successfully.');
    }

    public function submitApproval(AidBatch $aidBatch, AidBatchWorkflowService $workflowService, AuditLogService $auditLogService): AidBatchResource
    {
        $this->assertBatchScope($aidBatch);

        $oldValues = $aidBatch->toArray();
        $batch = $workflowService->submitForApproval($aidBatch, request()->user());
        $auditLogService->record('aid_batch.submitted_for_approval', $batch, $oldValues, $batch->toArray(), request());

        return new AidBatchResource($batch);
    }

    public function approve(
        Request $request,
        AidBatch $aidBatch,
        AidBatchWorkflowService $workflowService,
        IdempotencyService $idempotencyService,
        AuditLogService $auditLogService,
    ): JsonResponse {
        $this->assertBatchScope($aidBatch);

        if ($storedResponse = $idempotencyService->storedResponse($request)) {
            return $storedResponse;
        }

        $oldValues = $aidBatch->toArray();
        $batch = $workflowService->approve($aidBatch, $request->user());
        $auditLogService->record('aid_batch.approved', $batch, $oldValues, $batch->toArray(), $request);

        $body = ['data' => (new AidBatchResource($batch))->resolve($request)];
        $idempotencyService->rememberResponse($request, 200, $body);

        return response()->json($body);
    }

    public function cancel(AidBatch $aidBatch, AidBatchWorkflowService $workflowService, AuditLogService $auditLogService): AidBatchResource
    {
        $this->assertBatchScope($aidBatch);

        $oldValues = $aidBatch->toArray();
        $batch = $workflowService->cancel($aidBatch, request()->user());
        $auditLogService->record('aid_batch.cancelled', $batch, $oldValues, $batch->toArray(), request());

        return new AidBatchResource($batch);
    }

    public function complete(AidBatch $aidBatch, AidBatchWorkflowService $workflowService, AuditLogService $auditLogService): AidBatchResource
    {
        $this->assertBatchScope($aidBatch);

        $oldValues = $aidBatch->toArray();
        $batch = $workflowService->complete($aidBatch);
        $auditLogService->record('aid_batch.completed', $batch, $oldValues, $batch->toArray(), request());

        return new AidBatchResource($batch);
    }

    public function distributions(AidBatch $aidBatch): AnonymousResourceCollection
    {
        $this->assertBatchScope($aidBatch);

        $distributions = $aidBatch->distributions()
            ->with(['beneficiary', 'caseFile', 'deliveredBy', 'items.inventoryItem', 'reservations.stockLot'])
            ->withCount(['items', 'reservations'])
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return AidDistributionResource::collection($distributions);
    }

    public function storeDistribution(
        AidDistributionRequest $request,
        AidBatch $aidBatch,
        AidDistributionNumberGenerator $numberGenerator,
        BeneficiaryEligibilityService $eligibilityService,
        AuditLogService $auditLogService,
    ): AidDistributionResource {
        $this->assertBatchScope($aidBatch);
        $this->assertBatchMutable($aidBatch);

        $validated = $request->validated();
        $beneficiary = Beneficiary::where('organization_id', $request->user()->organization_id)->findOrFail($validated['beneficiary_id']);
        $caseFile = isset($validated['case_file_id']) ? CaseFile::where('organization_id', $request->user()->organization_id)->findOrFail($validated['case_file_id']) : null;

        $eligibilityService->assertEligible($beneficiary);
        $eligibilityService->assertCaseBelongsToBeneficiary($caseFile, $beneficiary);
        abort_if($aidBatch->distributions()->where('beneficiary_id', $beneficiary->id)->exists(), 422, 'Beneficiary is already included in this aid batch.');

        $distribution = $aidBatch->distributions()->create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'distribution_number' => $numberGenerator->generate($request->user()->organization_id),
            'status' => $aidBatch->status === 'pending_approval' ? 'pending_approval' : 'draft',
        ]);

        $auditLogService->record('aid_distribution.created', $distribution, null, $distribution->toArray(), $request);

        return new AidDistributionResource($distribution->load(['beneficiary', 'caseFile', 'items', 'reservations'])->loadCount(['items', 'reservations']));
    }

    public function eligibleBeneficiaries(AidBatch $aidBatch, Request $request, BeneficiaryEligibilityService $eligibilityService): AnonymousResourceCollection
    {
        $this->assertBatchScope($aidBatch);

        $beneficiaries = $eligibilityService->eligibleQuery($request->user()->organization_id, $aidBatch->branch_id)
            ->with(['branch'])
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('full_name')
            ->paginate($request->integer('per_page', 15));

        return BeneficiaryResource::collection($beneficiaries);
    }

    public function stockCheck(AidBatch $aidBatch, StockReservationService $stockReservationService): JsonResponse
    {
        $this->assertBatchScope($aidBatch);

        return response()->json(['data' => $stockReservationService->stockCheck($aidBatch)]);
    }

    private function assertBatchScope(AidBatch $aidBatch): void
    {
        abort_unless($aidBatch->organization_id === request()->user()->organization_id, 404);
    }

    private function assertBatchMutable(AidBatch $aidBatch): void
    {
        abort_unless(in_array($aidBatch->status, ['draft', 'pending_approval'], true), 422, 'This aid batch can no longer be edited.');
    }
}
