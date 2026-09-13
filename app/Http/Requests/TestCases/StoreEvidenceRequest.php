<?php

namespace App\Http\Requests\TestCases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('execution')->testCase) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'evidences' => ['required', 'array', 'min:1', 'max:10'],
            'evidences.*' => [
                'file',
                'max:10240',
                'mimetypes:image/png,image/jpeg,image/gif,image/webp,application/pdf,text/plain',
            ],
        ];
    }
}
