<?php

namespace App\Http\Requests\TestCases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'description' => ['nullable', 'string'],
            'classification_id' => ['required', 'integer', 'exists:classifications,id'],
            'template_id' => ['nullable', 'integer', 'exists:test_templates,id'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.description' => ['required', 'string'],
            'steps.*.expected_result' => ['nullable', 'string'],
        ];
    }
}
