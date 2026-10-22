<?php

namespace App\Http\Requests\TestCases;

use App\Models\Role;
use App\Models\TestCaseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTestCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('testCase'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'tested_method' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'classification_id' => ['nullable', 'integer', 'exists:classifications,id'],
            'template_id' => ['nullable', 'integer', 'exists:test_templates,id'],
            'status_id' => ['nullable', 'integer', Rule::exists('test_case_statuses', 'id')->where('project_id', $this->route('testCase')->project_id)],
            'assigned_to' => [
                'nullable',
                'integer',
                /** Only members holding an assignable role in this project. */
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', $this->route('testCase')->project_id)
                    ->whereIn('role_id', Role::query()->whereIn('slug', Role::ASSIGNABLE_SLUGS)->pluck('id')->all()),
            ],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.description' => ['required', 'string'],
            'steps.*.expected_result' => ['nullable', 'string'],
        ];
    }

    /**
     * A test case with an execution must keep a non-draft status.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $testCase = $this->route('testCase');
                $status = $this->input('status_id')
                    ? TestCaseStatus::find($this->integer('status_id'))
                    : null;

                if ($status?->name === 'Draft' && $testCase->executions()->exists()) {
                    $validator->errors()->add(
                        'status_id',
                        __('A test case with executions cannot return to Draft.'),
                    );
                }
            },
        ];
    }
}
