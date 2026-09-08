<?php

namespace App\Enums;

enum Permission: string
{
    case ManageAccounts = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageStatuses = 'statuses.manage';
    case AssignTestCases = 'test-cases.assign';

    /**
     * Get the human-readable label for the permission.
     */
    public function label(): string
    {
        return match ($this) {
            self::ManageAccounts => 'Manage user accounts',
            self::ManageRoles => 'Manage roles and permissions',
            self::ManageStatuses => 'Manage test case statuses',
            self::AssignTestCases => 'Assign test cases to other users',
        };
    }
}
