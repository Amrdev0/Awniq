<?php

namespace App\Services\Reports;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DonationReportService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $base = $this->baseQuery($organizationId, $filters);
        $confirmed = (clone $base)->where('payment_status', 'paid')->where('donation_status', 'confirmed');

        return [
            'summary' => [
                'total_count' => (clone $base)->count(),
                'total_amount' => number_format((float) (clone $base)->sum('amount'), 2, '.', ''),
                'confirmed_count' => (clone $confirmed)->count(),
                'confirmed_amount' => number_format((float) (clone $confirmed)->sum('amount'), 2, '.', ''),
                'pending_count' => (clone $base)->where('payment_status', 'pending')->count(),
                'cancelled_count' => (clone $base)->whereIn('donation_status', ['cancelled', 'refunded'])->count(),
            ],
            'by_payment_method' => $this->groupedRows((clone $base), 'payment_method'),
            'by_status' => (clone $base)
                ->select('payment_status', 'donation_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))
                ->groupBy('payment_status', 'donation_status')
                ->orderBy('payment_status')
                ->get()
                ->map(fn ($row): array => [
                    'payment_status' => $row->payment_status,
                    'donation_status' => $row->donation_status,
                    'count' => (int) $row->count,
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                ])
                ->all(),
            'by_campaign' => (clone $confirmed)
                ->leftJoin('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->select('campaigns.id', 'campaigns.title', DB::raw('COUNT(donations.id) as count'), DB::raw('SUM(donations.amount) as amount'))
                ->groupBy('campaigns.id', 'campaigns.title')
                ->orderBy('campaigns.title')
                ->get()
                ->map(fn ($row): array => [
                    'campaign_id' => $row->id,
                    'campaign_title' => $row->title ?? 'Unassigned',
                    'count' => (int) $row->count,
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                ])
                ->all(),
            'by_donor_type' => (clone $base)
                ->leftJoin('donors', 'donations.donor_id', '=', 'donors.id')
                ->select('donors.donor_type', DB::raw('COUNT(donations.id) as count'), DB::raw('SUM(donations.amount) as amount'))
                ->groupBy('donors.donor_type')
                ->orderBy('donors.donor_type')
                ->get()
                ->map(fn ($row): array => [
                    'donor_type' => $row->donor_type ?? 'anonymous',
                    'count' => (int) $row->count,
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function baseQuery(int $organizationId, array $filters = []): Builder
    {
        $query = Donation::query()->where('donations.organization_id', $organizationId);
        $this->filters->applyDateRange($query, $filters, 'donations.donated_at');
        $this->filters->applyCampaign($query, $filters, 'donations.campaign_id');

        return $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('donations.donation_status', $status))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('donations.payment_method', $method))
            ->when($filters['donor_type'] ?? null, fn (Builder $query, string $type) => $query->whereHas('donor', fn (Builder $query) => $query->where('donor_type', $type)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groupedRows(Builder $query, string $column): array
    {
        return $query
            ->select($column, DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->map(fn ($row): array => [
                $column => $row->{$column},
                'count' => (int) $row->count,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
            ])
            ->all();
    }
}
