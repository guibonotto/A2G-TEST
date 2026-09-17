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

        if ($target->is($this->user()) || ! $this->user()->outranks($target)) {
            return false;
        }

        // Admin may promote up to admin; everyone else only below their own level.
        return $newRole === null
            || $this->user()->hasRole('admin')
            || $this->user()->outranks($newRole);
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
