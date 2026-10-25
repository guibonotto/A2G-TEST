<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCases\ReportDefectRequest;
use App\Models\Execution;
use App\Services\Jira\JiraDefectReporter;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExecutionDefectController extends Controller
{
    /**
     * Report a failed execution as a Bug in the project's Jira.
     */
    public function store(ReportDefectRequest $request, Execution $execution, JiraDefectReporter $reporter): RedirectResponse
    {
        $testCase = $execution->testCase;
        $integration = $testCase->project->jiraIntegration;

        try {
            $failedUploads = $reporter->report(
                $integration,
                $execution,
                $request->user(),
                $request->validated('jira_project_key'),
                $request->validated('summary'),
                $request->boolean('attach_evidences'),
            );
        } catch (RequestException $exception) {
            return back()->withErrors([
                'execution' => $this->jiraErrorMessage($exception),
            ]);
        }

        Inertia::flash('toast', [
            'type' => $failedUploads > 0 ? 'warning' : 'success',
            'message' => $failedUploads > 0
                ? __('Defect :key created in Jira, but :count evidence file(s) could not be attached.', ['key' => $execution->jira_defect_key, 'count' => $failedUploads])
                : __('Defect :key created in Jira.', ['key' => $execution->jira_defect_key]),
        ]);

        return to_route('test-cases.show', $testCase);
    }

    /**
     * Jira explains a rejected issue in its own words (a project without the
     * Bug type, a required custom field...). Show that instead of a generic error.
     */
    private function jiraErrorMessage(RequestException $exception): string
    {
        $status = $exception->response->status();

        if ($status === 401 || $status === 403) {
            return __('Jira refused the request. Ask a project administrator to reconnect Jira so it grants write access.');
        }

        $details = collect($exception->response->json('errors') ?? [])
            ->merge($exception->response->json('errorMessages') ?? [])
            ->filter()
            ->implode(' ');

        return trim(__('Jira could not create the defect.').' '.$details);
    }
}
