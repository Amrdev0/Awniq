<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Services\AuditLogService;

class OrganizationController extends Controller
{
    public function show(): OrganizationResource
    {
        return new OrganizationResource(request()->user()->organization);
    }

    public function update(UpdateOrganizationRequest $request, AuditLogService $auditLogService): OrganizationResource
    {
        $organization = $request->user()->organization;
        $oldValues = $organization->only(array_keys($request->validated()));

        $organization->update($request->validated());

        $auditLogService->record('organization.updated', $organization, $oldValues, $organization->fresh()->toArray(), $request);

        return new OrganizationResource($organization->fresh());
    }
}
