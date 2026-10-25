<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StartController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'project'])->group(function () {
    Route::get('start', StartController::class)->name('start');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/projects.php';
require __DIR__.'/requirements.php';
require __DIR__.'/test-cases.php';
require __DIR__.'/management.php';
require __DIR__.'/jira.php';
