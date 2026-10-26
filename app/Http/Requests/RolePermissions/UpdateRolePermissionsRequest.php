<?php

namespace App\Http\Requests\RolePermissions;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRolePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Role $role */
        $role = $this->route('role');

        return $this->user()->canEditPermissionsOf($role);
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

    /**
     * Two rules the screen also enforces, repeated here because the screen is
     * not the only way to reach this endpoint.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Role $role */
                $role = $this->route('role');

                $permissions = $this->validated('permissions') ?? [];

                /**
                 * A read-only role that can be granted write permissions is
                 * read-only in name only.
                 */
                if ($role->slug === Role::VIEWER && $permissions !== []) {
                    $validator->errors()->add(
                        'permissions',
                        __('The viewer role is read-only and cannot be granted permissions.'),
                    );

                    return;
                }

                foreach ($permissions as $value) {
                    $permission = Permission::tryFrom($value);

                    if ($permission === null) {
                        continue;
                    }

                    foreach ($permission->requires() as $required) {
                        if (! in_array($required->value, $permissions, true)) {
                            $validator->errors()->add(
                                'permissions',
                                __('":permission" also needs ":required".', [
                                    'permission' => $permission->label(),
                                    'required' => $required->label(),
                                ]),
                            );
                        }
                    }
                }
            },
        ];
    }
}
