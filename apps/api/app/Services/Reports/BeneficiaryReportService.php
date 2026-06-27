<?php

namespace App\Services\Reports;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BeneficiaryReportService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $base = Beneficiary::query()->where('beneficiaries.organization_id', $organizationId);
        $this->filters->applyBranch($base, $filters, 'branch_id');

        return [
            'summary' => [
                'total_count' => (clone $base)->count(),
                'approved_count' => (clone $base)->where('status', 'approved')->count(),
                'pending_review_count' => (clone $base)->where('status', 'pending_review')->count(),
                'suspended_count' => (clone $base)->where('status', 'suspended')->count(),
            ],
            'by_status' => $this->groupCount((clone $base), 'status'),
            'by_vulnerability_level' => $this->groupCount((clone $base), 'vulnerability_level'),
            'by_branch' => (clone $base)
                ->leftJoin('branches', 'beneficiaries.branch_id', '=', 'branches.id')
                ->select('branches.id', 'branches.name', 'branches.code', DB::raw('COUNT(beneficiaries.id) as count'))
                ->groupBy('branches.id', 'branches.name', 'branches.code')
                ->orderBy('branches.name')
                ->get()
                ->map(fn ($row): array => [
                    'branch_id' => $row->id,
                    'branch' => $row->code ? "{$row->code} - {$row->name}" : 'Unassigned',
                    'count' => (int) $row->count,
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groupCount(Builder $query, string $column): array
    {
        return $query
            ->select($column, DB::raw('COUNT(*) as count'))
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->map(fn ($row): array => [$column => $row->{$column}, 'count' => (int) $row->count])
            ->all();
    }
}
