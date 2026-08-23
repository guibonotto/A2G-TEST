<?php

namespace App\Http\Requests\TestCaseStatuses;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestCaseStatusRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('test_case_statuses', 'name')->ignore($this->route('testCaseStatus')),
            ],
            'color' => ['required', 'string', 'in:success,destructive,warning,secondary,info'],
        ];
    }
}
