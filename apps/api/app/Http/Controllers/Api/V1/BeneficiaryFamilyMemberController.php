<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BeneficiaryFamilyMemberRequest;
use App\Http\Resources\BeneficiaryFamilyMemberResource;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyMember;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BeneficiaryFamilyMemberController extends Controller
{
    public function index(Beneficiary $beneficiary): AnonymousResourceCollection
    {
        $this->assertBeneficiaryScope($beneficiary);

        $familyMembers = $beneficiary->familyMembers()
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('full_name', 'like', "%{$search}%")->orWhere('relationship', 'like', "%{$search}%")->orWhere('national_id', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return BeneficiaryFamilyMemberResource::collection($familyMembers);
    }

    public function store(BeneficiaryFamilyMemberRequest $request, Beneficiary $beneficiary, AuditLogService $auditLogService): BeneficiaryFamilyMemberResource
    {
        $this->assertBeneficiaryScope($beneficiary);

        $familyMember = $beneficiary->familyMembers()->create($request->validated());

        $auditLogService->record('beneficiary_family_member.created', $familyMember, null, $familyMember->toArray(), $request);

        return new BeneficiaryFamilyMemberResource($familyMember);
    }

    public function update(
        BeneficiaryFamilyMemberRequest $request,
        Beneficiary $beneficiary,
        BeneficiaryFamilyMember $familyMember,
        AuditLogService $auditLogService,
    ): BeneficiaryFamilyMemberResource {
        $this->assertFamilyMemberScope($beneficiary, $familyMember);

        $oldValues = $familyMember->toArray();
        $familyMember->update($request->validated());

        $auditLogService->record('beneficiary_family_member.updated', $familyMember, $oldValues, $familyMember->fresh()->toArray(), $request);

        return new BeneficiaryFamilyMemberResource($familyMember->fresh());
    }

    public function destroy(Beneficiary $beneficiary, BeneficiaryFamilyMember $familyMember, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertFamilyMemberScope($beneficiary, $familyMember);

        $oldValues = $familyMember->toArray();
        $familyMember->delete();

        $auditLogService->record('beneficiary_family_member.deleted', $familyMember, $oldValues, null, request());

        return ApiResponse::success(message: 'Family member deleted successfully.');
    }

    private function assertBeneficiaryScope(Beneficiary $beneficiary): void
    {
        abort_unless($beneficiary->organization_id === request()->user()->organization_id, 404);
    }

    private function assertFamilyMemberScope(Beneficiary $beneficiary, BeneficiaryFamilyMember $familyMember): void
    {
        $this->assertBeneficiaryScope($beneficiary);
        abort_unless($familyMember->beneficiary_id === $beneficiary->id, 404);
    }
}
