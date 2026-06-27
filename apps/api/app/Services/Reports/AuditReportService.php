<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AuditReportService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(int $organizationId, array $filters = []): array
    {
        $base = AuditLog::query()->where('organization_id', $organizationId);
        $this->filters->applyDateRange($base, $filters, 'created_at');

        return [
            'summary' => [
                'total_count' => (clone $base)->count(),
                'user_count' => (clone $base)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            ],
            'by_action' => (clone $base)
                ->select('action', DB::raw('COUNT(*) as count'))
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(25)
                ->get()
                ->map(fn ($row): array => ['action' => $row->action, 'count' => (int) $row->count])
                ->all(),
            'recent' => (clone $base)
                ->with('user')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'user' => $log->user?->name,
                    'created_at' => $log->created_at?->toISOString(),
                ])
                ->all(),
        ];
    }
}
