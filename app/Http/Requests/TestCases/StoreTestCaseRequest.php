<?php

namespace App\Http\Requests\TestCases;

use App\Models\Project;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'tested_method' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'classification_id' => ['nullable', 'integer', 'exists:classifications,id'],
            'template_id' => ['nullable', 'integer', 'exists:test_templates,id'],
            'status_id' => ['nullable', 'integer', Rule::exists('test_case_statuses', 'id')->where('project_id', Project::current()?->id)],
            'assigned_to' => [
                'nullable',
                'integer',
                /** Only members holding an assignable role in this project. */
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', Project::current()?->id)
                    ->whereIn('role_id', Role::query()->whereIn('slug', Role::ASSIGNABLE_SLUGS)->pluck('id')->all()),
            ],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.description' => ['required', 'string'],
            'steps.*.expected_result' => ['required', 'string'],
            'requirement_ids' => [Rule::prohibitedIf(fn () => $this->user()->hasRole('viewer')), 'array'],
            'requirement_ids.*' => [
                'integer',
                Rule::exists('requirements', 'id')->where('project_id', Project::current()?->id)->withoutTrashed(),
            ],
        ];
    }
}
