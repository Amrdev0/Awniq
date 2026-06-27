<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BeneficiaryController;
use App\Http\Controllers\Api\V1\BeneficiaryFamilyMemberController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CaseDocumentController;
use App\Http\Controllers\Api\V1\CaseFileController;
use App\Http\Controllers\Api\V1\CaseNoteController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class)->name('api.v1.health');

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::get('organization', [OrganizationController::class, 'show'])->middleware('can:organization.view');
    Route::patch('organization', [OrganizationController::class, 'update'])->middleware('can:organization.update');

    Route::get('branches', [BranchController::class, 'index'])->middleware('can:branches.view');
    Route::post('branches', [BranchController::class, 'store'])->middleware('can:branches.create');
    Route::get('branches/{branch}', [BranchController::class, 'show'])->middleware('can:branches.view');
    Route::patch('branches/{branch}', [BranchController::class, 'update'])->middleware('can:branches.update');
    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('can:branches.delete');

    Route::post('users/{user}/enable', [UserController::class, 'enable'])->middleware('can:users.disable');
    Route::post('users/{user}/disable', [UserController::class, 'disable'])->middleware('can:users.disable');
    Route::post('users/{user}/roles', [UserController::class, 'syncRoles'])->middleware('can:users.assign_roles');
    Route::get('users', [UserController::class, 'index'])->middleware('can:users.view');
    Route::post('users', [UserController::class, 'store'])->middleware('can:users.create');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('can:users.view');
    Route::patch('users/{user}', [UserController::class, 'update'])->middleware('can:users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete');

    Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.view');
    Route::post('roles', [RoleController::class, 'store'])->middleware('can:roles.create');
    Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('can:roles.view');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete');

    Route::get('permissions', [PermissionController::class, 'index'])->middleware('can:permissions.view');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:audit_logs.view');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('can:audit_logs.view');

    Route::get('beneficiaries', [BeneficiaryController::class, 'index'])->middleware('can:beneficiaries.view');
    Route::post('beneficiaries', [BeneficiaryController::class, 'store'])->middleware('can:beneficiaries.create');
    Route::get('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'show'])->middleware('can:beneficiaries.view');
    Route::patch('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'update'])->middleware('can:beneficiaries.update');
    Route::delete('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'destroy'])->middleware('can:beneficiaries.delete');
    Route::post('beneficiaries/{beneficiary}/submit-review', [BeneficiaryController::class, 'submitReview'])->middleware('can:beneficiaries.submit_review');
    Route::post('beneficiaries/{beneficiary}/approve', [BeneficiaryController::class, 'approve'])->middleware('can:beneficiaries.approve');
    Route::post('beneficiaries/{beneficiary}/reject', [BeneficiaryController::class, 'reject'])->middleware('can:beneficiaries.reject');
    Route::post('beneficiaries/{beneficiary}/suspend', [BeneficiaryController::class, 'suspend'])->middleware('can:beneficiaries.suspend');
    Route::post('beneficiaries/{beneficiary}/reactivate', [BeneficiaryController::class, 'reactivate'])->middleware('can:beneficiaries.reactivate');
    Route::get('beneficiaries/{beneficiary}/duplicate-candidates', [BeneficiaryController::class, 'duplicateCandidates'])->middleware('can:beneficiaries.view');

    Route::get('beneficiaries/{beneficiary}/family-members', [BeneficiaryFamilyMemberController::class, 'index'])->middleware('can:beneficiary_family.view');
    Route::post('beneficiaries/{beneficiary}/family-members', [BeneficiaryFamilyMemberController::class, 'store'])->middleware('can:beneficiary_family.manage');
    Route::patch('beneficiaries/{beneficiary}/family-members/{familyMember}', [BeneficiaryFamilyMemberController::class, 'update'])->middleware('can:beneficiary_family.manage');
    Route::delete('beneficiaries/{beneficiary}/family-members/{familyMember}', [BeneficiaryFamilyMemberController::class, 'destroy'])->middleware('can:beneficiary_family.manage');

    Route::get('case-files', [CaseFileController::class, 'index'])->middleware('can:case_files.view');
    Route::post('case-files', [CaseFileController::class, 'store'])->middleware('can:case_files.create');
    Route::get('case-files/{caseFile}', [CaseFileController::class, 'show'])->middleware('can:case_files.view');
    Route::patch('case-files/{caseFile}', [CaseFileController::class, 'update'])->middleware('can:case_files.update');
    Route::delete('case-files/{caseFile}', [CaseFileController::class, 'destroy'])->middleware('can:case_files.delete');
    Route::post('case-files/{caseFile}/submit-review', [CaseFileController::class, 'submitReview'])->middleware('can:case_files.review');
    Route::post('case-files/{caseFile}/approve', [CaseFileController::class, 'approve'])->middleware('can:case_files.approve');
    Route::post('case-files/{caseFile}/reject', [CaseFileController::class, 'reject'])->middleware('can:case_files.reject');
    Route::post('case-files/{caseFile}/suspend', [CaseFileController::class, 'suspend'])->middleware('can:case_files.suspend');
    Route::post('case-files/{caseFile}/close', [CaseFileController::class, 'close'])->middleware('can:case_files.close');
    Route::post('case-files/{caseFile}/reopen', [CaseFileController::class, 'reopen'])->middleware('can:case_files.reopen');

    Route::get('case-files/{caseFile}/notes', [CaseNoteController::class, 'index'])->middleware('can:case_notes.view');
    Route::post('case-files/{caseFile}/notes', [CaseNoteController::class, 'store'])->middleware('can:case_notes.create');
    Route::patch('case-files/{caseFile}/notes/{caseNote}', [CaseNoteController::class, 'update'])->middleware('can:case_notes.update');
    Route::delete('case-files/{caseFile}/notes/{caseNote}', [CaseNoteController::class, 'destroy'])->middleware('can:case_notes.delete');

    Route::get('case-files/{caseFile}/documents', [CaseDocumentController::class, 'index'])->middleware('can:case_documents.view');
    Route::post('case-files/{caseFile}/documents', [CaseDocumentController::class, 'store'])->middleware('can:case_documents.upload');
    Route::get('case-files/{caseFile}/documents/{caseDocument}/download', [CaseDocumentController::class, 'download'])->middleware('can:case_documents.download');
    Route::delete('case-files/{caseFile}/documents/{caseDocument}', [CaseDocumentController::class, 'destroy'])->middleware('can:case_documents.delete');
});
