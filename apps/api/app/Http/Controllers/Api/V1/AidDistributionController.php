<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AidDistributionRequest;
use App\Http\Requests\DeliveryProofRequest;
use App\Http\Requests\MarkDeliveredRequest;
use App\Http\Requests\MarkFailedDistributionRequest;
use App\Http\Requests\RescheduleDistributionRequest;
use App\Http\Resources\AidDistributionResource;
use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\Beneficiary;
use App\Models\CaseFile;
use App\Services\AidDeliveryService;
use App\Services\AuditLogService;
use App\Services\BeneficiaryEligibilityService;
use App\Services\IdempotencyService;
use App\Services\Notifications\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AidDistributionController extends Controller
{
    public function updateInBatch(
        AidDistributionRequest $request,
        AidBatch $aidBatch,
        AidDistribution $distribution,
        BeneficiaryEligibilityService $eligibilityService,
        AuditLogService $auditLogService,
    ): AidDistributionResource {
        $this->assertBatchDistributionScope($aidBatch, $distribution);

        return $this->updateDistribution($request, $distribution, $eligibilityService, $auditLogService);
    }

    public function destroyInBatch(AidBatch $aidBatch, AidDistribution $distribution, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertBatchDistributionScope($aidBatch, $distribution);
        $this->assertDistributionMutable($distribution);
        abort_if($distribution->reservations()->exists(), 422, 'Distributions with stock reservations cannot be deleted.');

        $oldValues = $distribution->toArray();
        $distribution->delete();

        $auditLogService->record('aid_distribution.deleted', $distribution, $oldValues, null, request());

        return ApiResponse::success(message: 'Aid distribution deleted successfully.');
    }

    public function show(AidDistribution $distribution): AidDistributionResource
    {
        $this->assertDistributionScope($distribution);

        return new AidDistributionResource($distribution->load(['aidBatch', 'beneficiary', 'caseFile', 'deliveredBy', 'items.inventoryItem', 'items.stockLot', 'items.reservations.stockLot', 'reservations.stockLot'])->loadCount(['items', 'reservations']));
    }

    public function update(
        AidDistributionRequest $request,
        AidDistribution $distribution,
        BeneficiaryEligibilityService $eligibilityService,
        AuditLogService $auditLogService,
    ): AidDistributionResource {
        $this->assertDistributionScope($distribution);

        return $this->updateDistribution($request, $distribution, $eligibilityService, $auditLogService);
    }

    public function markDelivered(
        MarkDeliveredRequest $request,
        AidDistribution $distribution,
        AidDeliveryService $deliveryService,
        IdempotencyService $idempotencyService,
        AuditLogService $auditLogService,
    ): JsonResponse {
        $this->assertDistributionScope($distribution);

        if ($storedResponse = $idempotencyService->storedResponse($request)) {
            return $storedResponse;
        }

        $oldValues = $distribution->toArray();
        $proofData = $this->proofData($request, $distribution);
        $deliveredDistribution = $deliveryService->markDelivered($distribution, $request->user(), $proofData);
        $auditLogService->record('aid_distribution.delivered', $deliveredDistribution, $oldValues, $deliveredDistribution->toArray(), $request);

        $body = ['data' => (new AidDistributionResource($deliveredDistribution))->resolve($request)];
        $idempotencyService->rememberResponse($request, 200, $body);

        return response()->json($body);
    }

    public function markFailed(
        MarkFailedDistributionRequest $request,
        AidDistribution $distribution,
        AidDeliveryService $deliveryService,
        AuditLogService $auditLogService,
        NotificationService $notifications,
    ): AidDistributionResource {
        $this->assertDistributionScope($distribution);

        $oldValues = $distribution->toArray();
        $failedDistribution = $deliveryService->markFailed(
            $distribution,
            $request->user(),
            $request->validated('failure_reason'),
            $request->validated('notes'),
        );
        $auditLogService->record('aid_distribution.failed', $failedDistribution, $oldValues, $failedDistribution->toArray(), $request);
        $notifications->distributionChanged($failedDistribution, 'failed');

        return new AidDistributionResource($failedDistribution);
    }

    public function reschedule(
        RescheduleDistributionRequest $request,
        AidDistribution $distribution,
        AidDeliveryService $deliveryService,
        AuditLogService $auditLogService,
        NotificationService $notifications,
    ): AidDistributionResource {
        $this->assertDistributionScope($distribution);

        $oldValues = $distribution->toArray();
        $rescheduledDistribution = $deliveryService->reschedule(
            $distribution,
            $request->validated('scheduled_at'),
            $request->validated('notes'),
        );
        $auditLogService->record('aid_distribution.rescheduled', $rescheduledDistribution, $oldValues, $rescheduledDistribution->toArray(), $request);
        $notifications->distributionChanged($rescheduledDistribution, 'rescheduled');

        return new AidDistributionResource($rescheduledDistribution);
    }

    public function proof(
        DeliveryProofRequest $request,
        AidDistribution $distribution,
        AidDeliveryService $deliveryService,
        AuditLogService $auditLogService,
    ): AidDistributionResource {
        $this->assertDistributionScope($distribution);
        abort_if(in_array($distribution->status, ['cancelled', 'failed'], true), 422, 'Proof cannot be attached to cancelled or failed distributions.');

        $oldValues = $distribution->toArray();
        $proofDistribution = $deliveryService->attachProof($distribution, $this->proofData($request, $distribution));
        $auditLogService->record('aid_distribution.proof_attached', $proofDistribution, $oldValues, $proofDistribution->toArray(), $request);

        return new AidDistributionResource($proofDistribution);
    }

    private function updateDistribution(
        AidDistributionRequest $request,
        AidDistribution $distribution,
        BeneficiaryEligibilityService $eligibilityService,
        AuditLogService $auditLogService,
    ): AidDistributionResource {
        $this->assertDistributionMutable($distribution);

        $validated = $request->validated();
        $beneficiaryId = $validated['beneficiary_id'] ?? $distribution->beneficiary_id;
        $caseFileId = array_key_exists('case_file_id', $validated) ? $validated['case_file_id'] : $distribution->case_file_id;
        $beneficiary = Beneficiary::where('organization_id', $request->user()->organization_id)->findOrFail($beneficiaryId);
        $caseFile = $caseFileId ? CaseFile::where('organization_id', $request->user()->organization_id)->findOrFail($caseFileId) : null;

        $eligibilityService->assertEligible($beneficiary);
        $eligibilityService->assertCaseBelongsToBeneficiary($caseFile, $beneficiary);

        if ($beneficiary->id !== $distribution->beneficiary_id) {
            abort_if(
                $distribution->aidBatch->distributions()->where('beneficiary_id', $beneficiary->id)->whereKeyNot($distribution->id)->exists(),
                422,
                'Beneficiary is already included in this aid batch.',
            );
        }

        $oldValues = $distribution->toArray();
        $distribution->update($validated);
        $auditLogService->record('aid_distribution.updated', $distribution, $oldValues, $distribution->fresh()->toArray(), $request);

        return new AidDistributionResource($distribution->fresh()->load(['aidBatch', 'beneficiary', 'caseFile', 'items.inventoryItem', 'reservations.stockLot'])->loadCount(['items', 'reservations']));
    }

    private function proofData(MarkDeliveredRequest|DeliveryProofRequest $request, AidDistribution $distribution): array
    {
        $validated = $request->validated();
        $proofFilePath = $distribution->proof_file_path;
        $signaturePath = $distribution->beneficiary_signature_path;

        if ($request->hasFile('file')) {
            $proofFilePath = $request->file('file')->store("delivery-proofs/{$distribution->organization_id}/{$distribution->id}", 'local');
        }

        if ($request->hasFile('beneficiary_signature_file')) {
            $signaturePath = $request->file('beneficiary_signature_file')->store("delivery-proofs/{$distribution->organization_id}/{$distribution->id}/signatures", 'local');
        }

        return [
            'proof_type' => $validated['proof_type'],
            'proof_file_path' => $proofFilePath,
            'beneficiary_signature_path' => $signaturePath,
            'otp_code' => $validated['otp_code'] ?? $distribution->otp_code,
            'notes' => $validated['notes'] ?? $distribution->notes,
        ];
    }

    private function assertBatchDistributionScope(AidBatch $aidBatch, AidDistribution $distribution): void
    {
        abort_unless($aidBatch->organization_id === request()->user()->organization_id, 404);
        abort_unless($distribution->organization_id === request()->user()->organization_id, 404);
        abort_unless($distribution->aid_batch_id === $aidBatch->id, 404);
    }

    private function assertDistributionScope(AidDistribution $distribution): void
    {
        abort_unless($distribution->organization_id === request()->user()->organization_id, 404);
    }

    private function assertDistributionMutable(AidDistribution $distribution): void
    {
        $distribution->loadMissing('aidBatch');
        abort_unless(in_array($distribution->aidBatch->status, ['draft', 'pending_approval'], true), 422, 'This distribution can no longer be edited.');
        abort_unless(in_array($distribution->status, ['draft', 'pending_approval'], true), 422, 'This distribution can no longer be edited.');
    }
}
