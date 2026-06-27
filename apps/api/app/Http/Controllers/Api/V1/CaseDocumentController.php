<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CaseDocumentRequest;
use App\Http\Resources\CaseDocumentResource;
use App\Models\CaseDocument;
use App\Models\CaseFile;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseDocumentController extends Controller
{
    public function index(CaseFile $caseFile): AnonymousResourceCollection
    {
        $this->assertCaseScope($caseFile);

        $documents = $caseFile->documents()
            ->with('uploader')
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return CaseDocumentResource::collection($documents);
    }

    public function store(CaseDocumentRequest $request, CaseFile $caseFile, AuditLogService $auditLogService): CaseDocumentResource
    {
        $this->assertCaseScope($caseFile);

        $file = $request->file('file');
        $filePath = $file->store("case-documents/{$caseFile->organization_id}/{$caseFile->id}", 'local');

        $document = $caseFile->documents()->create([
            'organization_id' => $caseFile->organization_id,
            'beneficiary_id' => $caseFile->beneficiary_id,
            'uploaded_by' => $request->user()->id,
            'document_type' => $request->validated('document_type'),
            'file_path' => $filePath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'status' => 'active',
        ]);

        $auditLogService->record('case_document.created', $document, null, $document->toArray(), $request);

        return new CaseDocumentResource($document->load('uploader'));
    }

    public function download(CaseFile $caseFile, CaseDocument $caseDocument): StreamedResponse
    {
        $this->assertDocumentScope($caseFile, $caseDocument);
        abort_unless(Storage::disk('local')->exists($caseDocument->file_path), 404);

        return Storage::disk('local')->download($caseDocument->file_path, $caseDocument->original_filename);
    }

    public function destroy(CaseFile $caseFile, CaseDocument $caseDocument, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertDocumentScope($caseFile, $caseDocument);

        $oldValues = $caseDocument->toArray();
        $caseDocument->update(['status' => 'deleted']);
        $caseDocument->delete();

        $auditLogService->record('case_document.deleted', $caseDocument, $oldValues, null, request());

        return ApiResponse::success(message: 'Case document deleted successfully.');
    }

    private function assertCaseScope(CaseFile $caseFile): void
    {
        abort_unless($caseFile->organization_id === request()->user()->organization_id, 404);
    }

    private function assertDocumentScope(CaseFile $caseFile, CaseDocument $caseDocument): void
    {
        $this->assertCaseScope($caseFile);
        abort_unless($caseDocument->case_file_id === $caseFile->id, 404);
        abort_unless($caseDocument->organization_id === request()->user()->organization_id, 404);
    }
}
