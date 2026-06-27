<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\Reports\AuditReportService;
use App\Services\Reports\BeneficiaryReportService;
use App\Services\Reports\CampaignReportService;
use App\Services\Reports\CaseReportService;
use App\Services\Reports\DashboardService;
use App\Services\Reports\DistributionReportService;
use App\Services\Reports\DonationReportService;
use App\Services\Reports\InventoryReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function dashboard(DashboardService $dashboardService): JsonResponse
    {
        return response()->json(['data' => $dashboardService->data(request()->user()->organization_id)]);
    }

    public function donations(ReportFilterRequest $request, DonationReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }

    public function campaigns(ReportFilterRequest $request, CampaignReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }

    public function beneficiaries(ReportFilterRequest $request, BeneficiaryReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }

    public function caseFiles(ReportFilterRequest $request, CaseReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }

    public function distributions(ReportFilterRequest $request, DistributionReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }

    public function inventory(ReportFilterRequest $request, InventoryReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }

    public function auditLogs(ReportFilterRequest $request, AuditReportService $reportService): JsonResponse
    {
        return response()->json(['data' => $reportService->report($request->user()->organization_id, $request->filters())]);
    }
}
