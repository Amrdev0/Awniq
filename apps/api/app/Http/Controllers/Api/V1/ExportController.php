<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportRequest;
use App\Http\Resources\ExportResource;
use App\Models\Export;
use App\Services\AuditLogService;
use App\Services\Reports\ExportService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $exports = Export::query()
            ->with('user')
            ->where('organization_id', request()->user()->organization_id)
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('report_type', 'like', "%{$search}%")->orWhere('status', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return ExportResource::collection($exports);
    }

    public function store(ExportRequest $request, ExportService $exportService, AuditLogService $auditLogService): ExportResource
    {
        $validated = $request->validated();
        abort_unless($request->user()->can($this->reportPermission($validated['report_type'])), 403);

        $export = $exportService->create(
            $request->user(),
            $validated['report_type'],
            $validated['format'] ?? 'csv',
            $validated['filters'] ?? [],
        );

        $auditLogService->record('export.created', $export, null, $export->toArray(), $request);

        return new ExportResource($export->load('user'));
    }

    public function show(Export $export): ExportResource
    {
        $this->assertExportScope($export);

        return new ExportResource($export->load('user'));
    }

    public function download(Export $export): StreamedResponse
    {
        $this->assertExportScope($export);
        abort_unless($export->status === 'completed' && $export->file_path, 404);
        abort_unless(Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download($export->file_path, "{$export->report_type}-export-{$export->id}.{$export->format}");
    }

    private function assertExportScope(Export $export): void
    {
        abort_unless($export->organization_id === request()->user()->organization_id, 404);
    }

    private function reportPermission(string $reportType): string
    {
        return match ($reportType) {
            'donations' => 'reports.donations.view',
            'campaigns' => 'reports.campaigns.view',
            'beneficiaries' => 'reports.beneficiaries.view',
            'case_files' => 'reports.case_files.view',
            'distributions' => 'reports.distributions.view',
            'inventory' => 'reports.inventory.view',
            'audit_logs' => 'reports.audit_logs.view',
        };
    }
}
