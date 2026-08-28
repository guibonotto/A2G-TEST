<?php

namespace App\Http\Requests\TestCases;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTestCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('testCase'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn(
                        'role_id',
                        Role::query()->whereIn('slug', ['qa', 'developer'])->pluck('id')
                    )
                ),
            ],
        ];
    }
}
