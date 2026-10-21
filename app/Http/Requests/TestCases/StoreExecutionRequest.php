<?php

namespace App\Http\Requests\TestCases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('testCase')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:APROVADO,REPROVADO,BLOQUEADO,PENDENTE'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'obtained_output' => ['required_if:status,REPROVADO', 'nullable', 'string', 'max:10000'],
            'execution_date' => ['required', 'date'],
            'evidences' => ['nullable', 'array', 'max:10'],
            'evidences.*' => [
                'file',
                'max:10240',
                'mimetypes:image/png,image/jpeg,image/gif,image/webp,application/pdf,text/plain',
            ],
        ];
    }

    /**
     * A draft test case is not ready to be executed.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $testCase = $this->route('testCase');

                if ($testCase->status?->name === 'Draft') {
                    $validator->errors()->add(
                        'testCase',
                        __('A test case must leave Draft before an execution can be registered.'),
                    );
                }
            },
        ];
    }
}
