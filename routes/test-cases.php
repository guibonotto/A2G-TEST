<?php

use App\Http\Controllers\TestCaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('test-cases', [TestCaseController::class, 'index'])->name('test-cases.index');
    Route::get('test-cases/create', [TestCaseController::class, 'create'])->name('test-cases.create');
    Route::post('test-cases', [TestCaseController::class, 'store'])->name('test-cases.store');
    Route::get('test-cases/{testCase}', [TestCaseController::class, 'show'])->name('test-cases.show');
});
