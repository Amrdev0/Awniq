<?php

namespace App\Services\Reports;

use App\Models\AidDistribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DistributionReportService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $base = AidDistribution::query()
            ->where('aid_distributions.organization_id', $organizationId)
            ->join('aid_batches', 'aid_distributions.aid_batch_id', '=', 'aid_batches.id');

        $this->filters->applyDateRange($base, $filters, 'aid_distributions.delivered_at');
        $this->filters->applyBranch($base, $filters, 'aid_batches.branch_id');
        $this->filters->applyCampaign($base, $filters, 'aid_batches.campaign_id');
        $base->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('aid_distributions.status', $status));

        return [
            'summary' => [
                'total_count' => (clone $base)->count(),
                'delivered_count' => (clone $base)->where('aid_distributions.status', 'delivered')->count(),
                'failed_count' => (clone $base)->where('aid_distributions.status', 'failed')->count(),
                'rescheduled_count' => (clone $base)->where('aid_distributions.status', 'rescheduled')->count(),
            ],
            'by_status' => (clone $base)
                ->select('aid_distributions.status', DB::raw('COUNT(*) as count'))
                ->groupBy('aid_distributions.status')
                ->orderBy('aid_distributions.status')
                ->get()
                ->map(fn ($row): array => ['status' => $row->status, 'count' => (int) $row->count])
                ->all(),
            'by_batch' => (clone $base)
                ->select('aid_batches.id', 'aid_batches.batch_number', 'aid_batches.title', DB::raw('COUNT(aid_distributions.id) as count'))
                ->groupBy('aid_batches.id', 'aid_batches.batch_number', 'aid_batches.title')
                ->orderBy('aid_batches.batch_number')
                ->get()
                ->map(fn ($row): array => [
                    'aid_batch_id' => $row->id,
                    'batch_number' => $row->batch_number,
                    'title' => $row->title,
                    'count' => (int) $row->count,
                ])
                ->all(),
            'items_distributed' => DB::table('distribution_items')
                ->join('aid_distributions', 'distribution_items.aid_distribution_id', '=', 'aid_distributions.id')
                ->leftJoin('inventory_items', 'distribution_items.inventory_item_id', '=', 'inventory_items.id')
                ->where('distribution_items.organization_id', $organizationId)
                ->where('aid_distributions.status', 'delivered')
                ->select('inventory_items.id', 'inventory_items.sku', 'inventory_items.name', 'inventory_items.category', DB::raw('SUM(distribution_items.quantity) as quantity'))
                ->groupBy('inventory_items.id', 'inventory_items.sku', 'inventory_items.name', 'inventory_items.category')
                ->orderBy('inventory_items.name')
                ->get()
                ->map(fn ($row): array => [
                    'inventory_item_id' => $row->id,
                    'sku' => $row->sku,
                    'name' => $row->name,
                    'category' => $row->category,
                    'quantity' => number_format((float) $row->quantity, 3, '.', ''),
                ])
                ->all(),
        ];
    }
}
