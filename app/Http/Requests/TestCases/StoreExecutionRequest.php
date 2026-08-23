<?php

namespace App\Http\Requests\TestCases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:APROVADO,REPROVADO,BLOQUEADO,PENDENTE'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'execution_date' => ['required', 'date'],
        ];
    }
}