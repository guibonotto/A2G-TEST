<?php

namespace App\Http\Requests\TestCases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('testCase'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requirement_id' => [
                'required',
                'integer',
                Rule::exists('requirements', 'id')->where('project_id', $this->route('testCase')->project_id)->withoutTrashed(),
            ],
        ];
    }
}
