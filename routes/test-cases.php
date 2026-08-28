<?php

use App\Http\Controllers\TestCaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('test-cases', [TestCaseController::class, 'index'])->name('test-cases.index');
    Route::patch('test-cases/bulk-status', [TestCaseController::class, 'bulkUpdateStatus'])->name('test-cases.bulk-status');
    Route::get('test-cases/create', [TestCaseController::class, 'create'])->name('test-cases.create');
    Route::post('test-cases', [TestCaseController::class, 'store'])->name('test-cases.store');
    Route::get('test-cases/{testCase}', [TestCaseController::class, 'show'])->name('test-cases.show');
    Route::post('test-cases/{testCase}/executions', [TestCaseController::class, 'storeExecution'])->name('test-cases.executions.store');
    Route::get('test-cases/{testCase}/edit', [TestCaseController::class, 'edit'])->name('test-cases.edit');
    Route::put('test-cases/{testCase}', [TestCaseController::class, 'update'])->name('test-cases.update');
    Route::patch('test-cases/{testCase}/assign', [TestCaseController::class, 'assign'])->name('test-cases.assign');
    Route::delete('test-cases/{testCase}', [TestCaseController::class, 'deleteTestCase'])->name('test-cases.delete');
    Route::post('test-cases/{testCase}/requirements', [TestCaseController::class, 'linkRequirement'])->name('test-cases.requirements.link');
    Route::delete('test-cases/{testCase}/requirements', [TestCaseController::class, 'unlinkRequirement'])->name('test-cases.requirements.unlink');
});
