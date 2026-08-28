<?php

namespace App\Http\Requests\RolePermissions;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
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
            'permissions' => ['present', 'array'],
            'permissions.*' => [Rule::enum(Permission::class)],
        ];
    }
}
