<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CaseNoteRequest;
use App\Http\Resources\CaseNoteResource;
use App\Models\CaseFile;
use App\Models\CaseNote;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CaseNoteController extends Controller
{
    public function index(CaseFile $caseFile): AnonymousResourceCollection
    {
        $this->assertCaseScope($caseFile);

        $notes = $caseFile->notes()
            ->with('user')
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return CaseNoteResource::collection($notes);
    }

    public function store(CaseNoteRequest $request, CaseFile $caseFile, AuditLogService $auditLogService): CaseNoteResource
    {
        $this->assertCaseScope($caseFile);

        $note = $caseFile->notes()->create([
            ...$request->validated(),
            'visibility' => $request->validated('visibility') ?? 'internal',
            'user_id' => $request->user()->id,
        ]);

        $auditLogService->record('case_note.created', $note, null, $note->toArray(), $request);

        return new CaseNoteResource($note->load('user'));
    }

    public function update(CaseNoteRequest $request, CaseFile $caseFile, CaseNote $caseNote, AuditLogService $auditLogService): CaseNoteResource
    {
        $this->assertNoteScope($caseFile, $caseNote);

        $oldValues = $caseNote->toArray();
        $caseNote->update($request->validated());

        $auditLogService->record('case_note.updated', $caseNote, $oldValues, $caseNote->fresh()->toArray(), $request);

        return new CaseNoteResource($caseNote->fresh()->load('user'));
    }

    public function destroy(CaseFile $caseFile, CaseNote $caseNote, AuditLogService $auditLogService): JsonResponse
    {
        $this->assertNoteScope($caseFile, $caseNote);

        $oldValues = $caseNote->toArray();
        $caseNote->delete();

        $auditLogService->record('case_note.deleted', $caseNote, $oldValues, null, request());

        return ApiResponse::success(message: 'Case note deleted successfully.');
    }

    private function assertCaseScope(CaseFile $caseFile): void
    {
        abort_unless($caseFile->organization_id === request()->user()->organization_id, 404);
    }

    private function assertNoteScope(CaseFile $caseFile, CaseNote $caseNote): void
    {
        $this->assertCaseScope($caseFile);
        abort_unless($caseNote->case_file_id === $caseFile->id, 404);
    }
}
