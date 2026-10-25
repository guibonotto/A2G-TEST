<?php

use App\Http\Controllers\RequirementController;
use Illuminate\Support\Facades\Route;

/** Every project member reads the requirements they test against; only QA and admins change them. */
Route::middleware(['auth', 'verified', 'project'])->group(function () {
    Route::get('requirements', [RequirementController::class, 'index'])->name('requirements.index');

    Route::middleware('role:qa,admin')->group(function () {
        Route::post('requirements', [RequirementController::class, 'store'])->name('requirements.store');
        Route::put('requirements/{requirement}', [RequirementController::class, 'update'])->name('requirements.update');
        Route::delete('requirements/{requirement}', [RequirementController::class, 'destroy'])->name('requirements.destroy');
        Route::patch('requirements/{requirement}/restore', [RequirementController::class, 'restore'])->withTrashed()->name('requirements.restore');
    });
});
