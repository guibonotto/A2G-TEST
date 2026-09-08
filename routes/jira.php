<?php

use App\Http\Controllers\JiraIntegrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('jira/oauth/redirect', [JiraIntegrationController::class, 'redirect'])->name('jira.redirect');
    Route::get('jira/oauth/callback', [JiraIntegrationController::class, 'callback'])->name('jira.callback');
});

Route::middleware(['auth', 'verified', 'role:qa'])->group(function () {
    Route::get('management/jira', [JiraIntegrationController::class, 'edit'])->name('jira.edit');
    Route::delete('management/jira', [JiraIntegrationController::class, 'destroy'])->name('jira.destroy');
    Route::post('management/jira/import', [JiraIntegrationController::class, 'import'])->name('jira.import');
    Route::get('management/jira/projects/{projectKey}/issue-types', [JiraIntegrationController::class, 'issueTypes'])->name('jira.issue-types');
});
