<?php

namespace App\Http\Requests\Requirements;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('requirements', 'code')->where('project_id', Project::current()?->id),
            ],
            'type' => ['required', 'string', 'in:funcional,nao_funcional'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', 'in:baixa,media,alta'],
            'status' => ['nullable', 'string', 'in:pendente,em_andamento,concluido'],
        ];
    }
}
