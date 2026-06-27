<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $campaigns = Campaign::query()
            ->with('creator')
            ->withCount(['donations', 'allocations'])
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('visibility'), fn ($query, string $visibility) => $query->where('visibility', $visibility))
            ->when($request->query('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return CampaignResource::collection($campaigns);
    }

    public function store(CampaignRequest $request, AuditLogService $auditLogService): CampaignResource
    {
        $validated = $request->validated();

        $campaign = Campaign::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
            'status' => $validated['status'] ?? 'draft',
            'visibility' => $validated['visibility'] ?? 'private',
            'created_by' => $request->user()->id,
        ]);

        $auditLogService->record('campaign.created', $campaign, null, $campaign->toArray(), $request);

        return new CampaignResource($campaign->load('creator')->loadCount(['donations', 'allocations']));
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $this->assertCampaignScope($campaign);

        return new CampaignResource($campaign->load(['creator', 'donations.donor'])->loadCount(['donations', 'allocations']));
    }

    public function update(CampaignRequest $request, Campaign $campaign, AuditLogService $auditLogService): CampaignResource
    {
        $this->assertCampaignScope($campaign);

        $validated = $request->validated();
        unset($validated['status'], $validated['collected_amount']);

        $oldValues = $campaign->toArray();
        $campaign->update($validated);

        $auditLogService->record('campaign.updated', $campaign, $oldValues, $campaign->fresh()->toArray(), $request);

        return new CampaignResource($campaign->fresh()->load('creator')->loadCount(['donations', 'allocations']));
    }

    public function destroy(Campaign $campaign, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertCampaignScope($campaign);

        $oldValues = $campaign->toArray();
        $campaign->delete();

        $auditLogService->record('campaign.deleted', $campaign, $oldValues, null, request());

        return ApiResponse::success(message: 'Campaign deleted successfully.');
    }

    public function activate(Campaign $campaign, AuditLogService $auditLogService): CampaignResource
    {
        $this->assertCampaignScope($campaign);
        $this->assertStatusIn($campaign, ['draft', 'paused'], 'Only draft or paused campaigns can be activated.');

        return $this->transition($campaign, 'active', 'campaign.activated', $auditLogService);
    }

    public function pause(Campaign $campaign, AuditLogService $auditLogService): CampaignResource
    {
        $this->assertCampaignScope($campaign);
        $this->assertStatusIn($campaign, ['active'], 'Only active campaigns can be paused.');

        return $this->transition($campaign, 'paused', 'campaign.paused', $auditLogService);
    }

    public function complete(Campaign $campaign, AuditLogService $auditLogService): CampaignResource
    {
        $this->assertCampaignScope($campaign);
        $this->assertStatusIn($campaign, ['active', 'paused'], 'Only active or paused campaigns can be completed.');

        return $this->transition($campaign, 'completed', 'campaign.completed', $auditLogService);
    }

    public function cancel(Campaign $campaign, AuditLogService $auditLogService): CampaignResource
    {
        $this->assertCampaignScope($campaign);
        $this->assertStatusIn($campaign, ['draft', 'active', 'paused'], 'This campaign cannot be cancelled.');

        return $this->transition($campaign, 'cancelled', 'campaign.cancelled', $auditLogService);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function assertStatusIn(Campaign $campaign, array $statuses, string $message): void
    {
        abort_unless(in_array($campaign->status, $statuses, true), 422, $message);
    }

    private function transition(Campaign $campaign, string $status, string $action, AuditLogService $auditLogService): CampaignResource
    {
        $oldValues = ['status' => $campaign->status];
        $campaign->update(['status' => $status]);

        $auditLogService->record($action, $campaign, $oldValues, ['status' => $status], request());

        return new CampaignResource($campaign->fresh()->load('creator')->loadCount(['donations', 'allocations']));
    }

    private function assertCampaignScope(Campaign $campaign): void
    {
        abort_unless($campaign->organization_id === request()->user()->organization_id, 404);
    }
}
