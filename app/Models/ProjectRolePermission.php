<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The permission set a project chose for one role, replacing that role's
 * default set inside the project.
 */
#[Fillable(['project_id', 'role_id', 'permissions'])]
class ProjectRolePermission extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
