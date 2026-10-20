<?php

namespace App\Http\Requests\TestCases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Role;
use Illuminate\Validation\Rule;

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
            'status_id' => ['nullable', 'integer', 'exists:test_case_statuses,id'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn(
                        'role_id',
                        Role::query()->whereIn('slug', ['qa', 'developer'])->pluck('id'),
                    ),
                ),
            ],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.description' => ['required', 'string'],
            'steps.*.expected_result' => ['nullable', 'string'],
        ];
    }
}
