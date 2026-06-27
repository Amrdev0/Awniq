<?php

namespace App\Services\Reports;

use App\Models\AidBatch;
use App\Models\AidDistribution;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\CaseFile;
use App\Models\Donation;
use App\Services\StockReportService;

class DashboardService
{
    public function __construct(private readonly StockReportService $stockReportService) {}

    /**
     * @return array<string, mixed>
     */
    public function data(int $organizationId): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $lowStock = $this->stockReportService->lowStock($organizationId);
        $expiringStock = $this->stockReportService->expiring($organizationId, 30);

        return [
            'metrics' => [
                'total_donations_this_month' => number_format((float) Donation::query()
                    ->where('organization_id', $organizationId)
                    ->where('payment_status', 'paid')
                    ->where('donation_status', 'confirmed')
                    ->whereDate('confirmed_at', '>=', $monthStart)
                    ->whereDate('confirmed_at', '<=', $monthEnd)
                    ->sum('amount'), 2, '.', ''),
                'active_campaigns' => Campaign::where('organization_id', $organizationId)->where('status', 'active')->count(),
                'pending_cases' => CaseFile::where('organization_id', $organizationId)->whereIn('status', ['open', 'under_review'])->count(),
                'approved_beneficiaries' => Beneficiary::where('organization_id', $organizationId)->where('status', 'approved')->count(),
                'aid_batches_in_progress' => AidBatch::where('organization_id', $organizationId)->whereIn('status', ['approved', 'in_progress'])->count(),
                'completed_distributions' => AidDistribution::where('organization_id', $organizationId)->where('status', 'delivered')->count(),
                'low_stock_items' => count($lowStock),
                'expiring_stock_lots' => $expiringStock->count(),
            ],
            'alerts' => [
                'low_stock' => $lowStock,
                'expiring_stock' => $expiringStock->map(fn ($lot): array => [
                    'stock_lot_id' => $lot->id,
                    'sku' => $lot->inventoryItem?->sku,
                    'name' => $lot->inventoryItem?->name,
                    'warehouse' => $lot->warehouse?->code,
                    'remaining_quantity' => $lot->remaining_quantity,
                    'expiry_date' => $lot->expiry_date?->toDateString(),
                ])->values()->all(),
            ],
            'recent_activity' => AuditLog::query()
                ->with('user')
                ->where('organization_id', $organizationId)
                ->latest()
                ->limit(8)
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
