<?php

namespace App\Services\Reports;

use App\Models\CaseFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CaseReportService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $base = CaseFile::query()
            ->where('case_files.organization_id', $organizationId)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('case_files.status', $status))
            ->when($filters['branch_id'] ?? null, function (Builder $query, int|string $branchId): void {
                $query->whereHas('beneficiary', fn (Builder $query) => $query->where('branch_id', $branchId));
            });

        return [
            'summary' => [
                'total_count' => (clone $base)->count(),
                'approved_count' => (clone $base)->where('status', 'approved')->count(),
                'under_review_count' => (clone $base)->where('status', 'under_review')->count(),
                'overdue_followups' => (clone $base)
                    ->whereNotNull('next_follow_up_date')
                    ->whereDate('next_follow_up_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['closed', 'rejected'])
                    ->count(),
            ],
            'by_status' => (clone $base)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row): array => ['status' => $row->status, 'count' => (int) $row->count])
                ->all(),
            'by_assigned_user' => (clone $base)
                ->leftJoin('users', 'case_files.assigned_to_user_id', '=', 'users.id')
                ->select('users.id', 'users.name', DB::raw('COUNT(case_files.id) as count'))
                ->groupBy('users.id', 'users.name')
                ->orderBy('users.name')
                ->get()
                ->map(fn ($row): array => [
                    'user_id' => $row->id,
                    'user' => $row->name ?? 'Unassigned',
                    'count' => (int) $row->count,
                ])
                ->all(),
        ];
    }
}
