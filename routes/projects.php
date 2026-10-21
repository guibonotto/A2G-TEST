<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects')->name('projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::post('/', [ProjectController::class, 'store'])->name('store');
    Route::get('invite/{token}', [ProjectController::class, 'invite'])->name('invite');
    Route::post('{project}/invites', [ProjectController::class, 'createInvite'])->name('invites.store');
    Route::delete('{project}/invites/{invitation}', [ProjectController::class, 'revokeInvite'])->name('invites.destroy');
    Route::delete('{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('members.destroy');
    Route::post('{project}/select', [ProjectController::class, 'select'])->name('select');
    Route::get('{project}', [ProjectController::class, 'show'])->name('show');
});
