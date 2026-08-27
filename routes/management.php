<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\TestCaseStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:qa'])->group(function () {
    Route::get('management/statuses', [TestCaseStatusController::class, 'index'])->name('test-case-statuses.index');
    Route::post('management/statuses', [TestCaseStatusController::class, 'store'])->name('test-case-statuses.store');
    Route::put('management/statuses/{testCaseStatus}', [TestCaseStatusController::class, 'update'])->name('test-case-statuses.update');
    Route::delete('management/statuses/{testCaseStatus}', [TestCaseStatusController::class, 'destroy'])->name('test-case-statuses.destroy');

    Route::get('management/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::put('management/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');

    Route::get('management/permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    Route::put('management/permissions/{role}', [RolePermissionController::class, 'update'])->name('role-permissions.update');

    Route::get('management/requirements', [RequirementController::class, 'index'])->name('requirements.index');
    Route::post('management/requirements', [RequirementController::class, 'store'])->name('requirements.store');
    Route::put('management/requirements/{requirement}', [RequirementController::class, 'update'])->name('requirements.update');
    Route::delete('management/requirements/{requirement}', [RequirementController::class, 'destroy'])->name('requirements.destroy');
});
