<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
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
});
