<?php

namespace App\Http\Requests\TestCases;

use App\Models\TestCaseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTestCaseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('testCase')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['nullable', 'integer', 'exists:test_case_statuses,id'],
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
