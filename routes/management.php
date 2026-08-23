<?php

use App\Http\Controllers\TestCaseStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:qa'])->group(function () {
    Route::get('management/statuses', [TestCaseStatusController::class, 'index'])->name('test-case-statuses.index');
    Route::post('management/statuses', [TestCaseStatusController::class, 'store'])->name('test-case-statuses.store');
    Route::put('management/statuses/{testCaseStatus}', [TestCaseStatusController::class, 'update'])->name('test-case-statuses.update');
    Route::delete('management/statuses/{testCaseStatus}', [TestCaseStatusController::class, 'destroy'])->name('test-case-statuses.destroy');
});
