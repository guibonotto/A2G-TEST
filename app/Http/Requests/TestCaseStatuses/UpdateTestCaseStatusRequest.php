<?php

namespace App\Http\Requests\TestCaseStatuses;

use App\Models\Project;
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
                Rule::unique('test_case_statuses', 'name')->where('project_id', Project::current()?->id)->ignore($this->route('testCaseStatus')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => [
                'required',
                'string',
                'in:success,destructive,warning,secondary,info,blue,purple,orange,pink',
                Rule::unique('test_case_statuses', 'color')->where('project_id', Project::current()?->id)->ignore($this->route('testCaseStatus')),
            ],
            'category' => ['required', 'string', 'in:in_progress,approved,failed,cancelled'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));

        $this->merge([
            'name' => self::capitalizeName($name),
            'description' => trim((string) $this->input('description')) ?: null,
            'category' => $this->input('category', 'in_progress'),
        ]);
    }

    private static function capitalizeName(string $name): string
    {
        return mb_strtoupper(mb_substr(mb_strtolower($name), 0, 1)).mb_substr(mb_strtolower($name), 1);
    }
}
