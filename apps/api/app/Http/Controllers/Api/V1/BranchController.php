<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $branches = Branch::query()
            ->with('manager')
            ->where('organization_id', request()->user()->organization_id)
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return BranchResource::collection($branches);
    }

    public function store(BranchRequest $request, AuditLogService $auditLogService): BranchResource
    {
        $branch = Branch::create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization_id,
        ]);

        $auditLogService->record('branch.created', $branch, null, $branch->toArray(), $request);

        return new BranchResource($branch->load('manager'));
    }

    public function show(Branch $branch): BranchResource
    {
        $this->assertBranchScope($branch);

        return new BranchResource($branch->load('manager'));
    }

    public function update(BranchRequest $request, Branch $branch, AuditLogService $auditLogService): BranchResource
    {
        $this->assertBranchScope($branch);

        $oldValues = $branch->only(array_keys($request->validated()));
        $branch->update($request->validated());

        $auditLogService->record('branch.updated', $branch, $oldValues, $branch->fresh()->toArray(), $request);

        return new BranchResource($branch->fresh()->load('manager'));
    }

    public function destroy(Branch $branch, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertBranchScope($branch);

        $oldValues = $branch->toArray();
        $branch->delete();

        $auditLogService->record('branch.deleted', $branch, $oldValues, null, request());

        return ApiResponse::success(message: 'Branch deleted successfully.');
    }

    private function assertBranchScope(Branch $branch): void
    {
        abort_unless($branch->organization_id === request()->user()->organization_id, 404);
    }
}
