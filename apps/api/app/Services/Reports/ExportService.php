<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\CaseFile;
use App\Models\Donation;
use App\Models\Export;
use App\Models\User;
use App\Services\StockReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportService
{
    public function __construct(
        private readonly CsvExportWriter $csvWriter,
        private readonly StockReportService $stockReportService,
        private readonly ReportFilterService $filters,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(User $user, string $reportType, string $format = 'csv', array $filters = []): Export
    {
        $export = Export::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'report_type' => $reportType,
            'format' => $format,
            'filters' => $filters,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            [$headers, $rows] = $this->rows($user->organization_id, $reportType, $filters);
            $path = "exports/{$user->organization_id}/{$export->id}-{$reportType}.csv";
            Storage::disk('local')->put($path, $this->csvWriter->write($headers, $rows));

            $export->update([
                'status' => 'completed',
                'file_path' => $path,
                'completed_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            $export->update([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $export->fresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<string, mixed>>}
     */
    private function rows(int $organizationId, string $reportType, array $filters): array
    {
        return match ($reportType) {
            'donations' => $this->donationRows($organizationId, $filters),
            'campaigns' => $this->campaignRows($organizationId, $filters),
            'beneficiaries' => $this->beneficiaryRows($organizationId, $filters),
            'case_files' => $this->caseRows($organizationId, $filters),
            'distributions' => $this->distributionRows($organizationId, $filters),
            'inventory' => $this->inventoryRows($organizationId, $filters),
            'audit_logs' => $this->auditRows($organizationId, $filters),
        };
    }

    private function donationRows(int $organizationId, array $filters): array
    {
        $query = Donation::query()
            ->with(['donor', 'campaign'])
            ->where('organization_id', $organizationId);
        $this->filters->applyDateRange($query, $filters, 'donated_at');
        $this->filters->applyCampaign($query, $filters);
        $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('donation_status', $status))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method));

        return [
            ['donation_number', 'donor', 'campaign', 'amount', 'currency', 'payment_method', 'payment_status', 'donation_status', 'donated_at'],
            $query->orderByDesc('donated_at')->get()->map(fn (Donation $donation): array => [
                'donation_number' => $donation->donation_number,
                'donor' => $donation->donor?->name ?? 'Anonymous',
                'campaign' => $donation->campaign?->title,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
                'payment_method' => $donation->payment_method,
                'payment_status' => $donation->payment_status,
                'donation_status' => $donation->donation_status,
                'donated_at' => $donation->donated_at?->toISOString(),
            ])->all(),
        ];
    }

    private function campaignRows(int $organizationId, array $filters): array
    {
        $rows = Campaign::query()
            ->where('organization_id', $organizationId)
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, int|string $campaignId) => $query->whereKey($campaignId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('title')
            ->get()
            ->map(fn (Campaign $campaign): array => [
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'status' => $campaign->status,
                'goal_amount' => $campaign->goal_amount,
                'collected_amount' => $campaign->collected_amount,
                'currency' => $campaign->currency,
            ])
            ->all();

        return [['title', 'slug', 'status', 'goal_amount', 'collected_amount', 'currency'], $rows];
    }

    private function beneficiaryRows(int $organizationId, array $filters): array
    {
        $query = Beneficiary::query()->with('branch')->where('organization_id', $organizationId);
        $this->filters->applyBranch($query, $filters);

        return [
            ['code', 'full_name', 'branch', 'status', 'vulnerability_level', 'household_size', 'city'],
            $query->orderBy('code')->get()->map(fn (Beneficiary $beneficiary): array => [
                'code' => $beneficiary->code,
                'full_name' => $beneficiary->full_name,
                'branch' => $beneficiary->branch?->code,
                'status' => $beneficiary->status,
                'vulnerability_level' => $beneficiary->vulnerability_level,
                'household_size' => $beneficiary->household_size,
                'city' => $beneficiary->city,
            ])->all(),
        ];
    }

    private function caseRows(int $organizationId, array $filters): array
    {
        $query = CaseFile::query()->with(['beneficiary', 'assignedTo'])->where('organization_id', $organizationId);
        $query->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));

        return [
            ['case_number', 'beneficiary', 'case_type', 'priority', 'status', 'assigned_to', 'next_follow_up_date'],
            $query->orderBy('case_number')->get()->map(fn (CaseFile $caseFile): array => [
                'case_number' => $caseFile->case_number,
                'beneficiary' => $caseFile->beneficiary?->full_name,
                'case_type' => $caseFile->case_type,
                'priority' => $caseFile->priority,
                'status' => $caseFile->status,
                'assigned_to' => $caseFile->assignedTo?->name,
                'next_follow_up_date' => $caseFile->next_follow_up_date?->toDateString(),
            ])->all(),
        ];
    }

    private function distributionRows(int $organizationId, array $filters): array
    {
        $query = DB::table('aid_distributions')
            ->join('aid_batches', 'aid_distributions.aid_batch_id', '=', 'aid_batches.id')
            ->join('beneficiaries', 'aid_distributions.beneficiary_id', '=', 'beneficiaries.id')
            ->where('aid_distributions.organization_id', $organizationId);
        $this->filters->applyBranch($query, $filters, 'aid_batches.branch_id');
        $this->filters->applyCampaign($query, $filters, 'aid_batches.campaign_id');
        $query->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('aid_distributions.status', $status));

        return [
            ['distribution_number', 'batch_number', 'beneficiary', 'status', 'delivery_method', 'scheduled_at', 'delivered_at'],
            $query
                ->select('aid_distributions.distribution_number', 'aid_batches.batch_number', 'beneficiaries.full_name', 'aid_distributions.status', 'aid_distributions.delivery_method', 'aid_distributions.scheduled_at', 'aid_distributions.delivered_at')
                ->orderByDesc('aid_distributions.created_at')
                ->get()
                ->map(fn ($row): array => [
                    'distribution_number' => $row->distribution_number,
                    'batch_number' => $row->batch_number,
                    'beneficiary' => $row->full_name,
                    'status' => $row->status,
                    'delivery_method' => $row->delivery_method,
                    'scheduled_at' => $row->scheduled_at,
                    'delivered_at' => $row->delivered_at,
                ])
                ->all(),
        ];
    }

    private function inventoryRows(int $organizationId, array $filters): array
    {
        $summary = $this->stockReportService->summary($organizationId, isset($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : null);

        return [
            ['sku', 'name', 'category', 'unit', 'available_quantity', 'reserved_quantity', 'minimum_stock_level', 'low_stock'],
            collect($summary)->map(fn (array $row): array => [
                'sku' => $row['sku'],
                'name' => $row['name'],
                'category' => $row['category'],
                'unit' => $row['unit'],
                'available_quantity' => $row['available_quantity'],
                'reserved_quantity' => $row['reserved_quantity'],
                'minimum_stock_level' => $row['minimum_stock_level'],
                'low_stock' => $row['low_stock'] ? 'yes' : 'no',
            ])->all(),
        ];
    }

    private function auditRows(int $organizationId, array $filters): array
    {
        $query = AuditLog::query()->with('user')->where('organization_id', $organizationId);
        $this->filters->applyDateRange($query, $filters, 'created_at');

        return [
            ['id', 'action', 'entity_type', 'entity_id', 'user', 'created_at'],
            $query->latest()->limit(500)->get()->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'user' => $log->user?->name,
                'created_at' => $log->created_at?->toISOString(),
            ])->all(),
        ];
    }
}
