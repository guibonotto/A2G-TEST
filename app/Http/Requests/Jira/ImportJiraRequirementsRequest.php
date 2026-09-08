<?php

namespace App\Http\Requests\Jira;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportJiraRequirementsRequest extends FormRequest
{
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
            'project_key' => ['required', 'string', 'max:50'],
            'issue_types' => ['required', 'array', 'min:1'],
            'issue_types.*' => ['required', 'string', 'max:100'],
        ];
    }
}
