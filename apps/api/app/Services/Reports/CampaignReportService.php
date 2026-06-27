<?php

namespace App\Services\Reports;

use App\Models\AidDistribution;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;

class CampaignReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $campaigns = Campaign::query()
            ->where('organization_id', $organizationId)
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, int|string $campaignId) => $query->whereKey($campaignId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('title')
            ->get();

        $rows = $campaigns->map(function (Campaign $campaign) use ($organizationId): array {
            $donationCount = Donation::query()
                ->where('organization_id', $organizationId)
                ->where('campaign_id', $campaign->id)
                ->where('payment_status', 'paid')
                ->where('donation_status', 'confirmed')
                ->count();

            $distributionCount = AidDistribution::query()
                ->where('aid_distributions.organization_id', $organizationId)
                ->join('aid_batches', 'aid_distributions.aid_batch_id', '=', 'aid_batches.id')
                ->where('aid_batches.campaign_id', $campaign->id)
                ->count();

            $goal = (float) $campaign->goal_amount;
            $collected = (float) $campaign->collected_amount;

            return [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'visibility' => $campaign->visibility,
                'goal_amount' => number_format($goal, 2, '.', ''),
                'collected_amount' => number_format($collected, 2, '.', ''),
                'progress_percentage' => $goal > 0 ? round(($collected / $goal) * 100, 2) : 0,
                'donation_count' => $donationCount,
                'distribution_count' => $distributionCount,
            ];
        })->values()->all();

        return [
            'summary' => [
                'campaign_count' => count($rows),
                'active_campaign_count' => collect($rows)->where('status', 'active')->count(),
                'total_goal_amount' => number_format((float) collect($rows)->sum(fn (array $row): float => (float) $row['goal_amount']), 2, '.', ''),
                'total_collected_amount' => number_format((float) collect($rows)->sum(fn (array $row): float => (float) $row['collected_amount']), 2, '.', ''),
            ],
            'campaigns' => $rows,
        ];
    }
}
