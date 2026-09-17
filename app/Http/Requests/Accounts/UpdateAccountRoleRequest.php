<?php

namespace App\Http\Requests\Accounts;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('account');
        $newRole = Role::find($this->input('role_id'));

        return $this->user()->canChangeRoleOf($target)
            && $this->user()->canAssignRole($newRole);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }
}
