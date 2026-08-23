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
            self::ManageAccounts => 'Gerenciar contas de usuário',
            self::ManageRoles => 'Gerenciar papéis e permissões',
            self::ManageStatuses => 'Gerenciar status de casos de teste',
            self::AssignTestCases => 'Atribuir casos de teste a outros usuários',
        };
    }
}
