<?php

namespace App\Http\Requests\TestCases;

use App\Models\Execution;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportDefectRequest extends FormRequest
{
    /**
     * Reporting a defect writes to the team's Jira, so viewers cannot do it.
     */
    public function authorize(): bool
    {
        /** @var Execution $execution */
        $execution = $this->route('execution');

        return $this->user()?->can('update', $execution->testCase) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jira_project_key' => ['required', 'string', 'max:50'],
            'summary' => ['required', 'string', 'max:255'],
            'attach_evidences' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Execution $execution */
                $execution = $this->route('execution');

                if (! $execution->canReportDefect()) {
                    $validator->errors()->add('execution', __('Only a failed execution without a reported defect can be reported to Jira.'));

                    return;
                }

                $integration = $execution->testCase->project->jiraIntegration;

                if ($integration === null || ! $integration->canCreateIssues()) {
                    $validator->errors()->add('execution', __('Jira must be connected with write access to report defects. Ask a project administrator to reconnect it.'));
                }
            },
        ];
    }
}
