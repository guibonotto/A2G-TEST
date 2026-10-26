<?php

namespace App\Enums;

enum Permission: string
{
    case ManageAccounts = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageRequirements = 'requirements.manage';
    case ManageTestCases = 'test-cases.manage';
    case DeleteTestCases = 'test-cases.delete';
    case AssignTestCases = 'test-cases.assign';
    case ManageStatuses = 'statuses.manage';
    case RecordExecutions = 'executions.record';
    case ManageJira = 'jira.manage';

    /**
     * Get the human-readable label for the permission.
     */
    public function label(): string
    {
        return match ($this) {
            self::ManageAccounts => 'Manage user accounts',
            self::ManageRoles => 'Manage roles and permissions',
            self::ManageRequirements => 'Create, edit and archive requirements',
            self::ManageTestCases => 'Create and edit test cases',
            self::DeleteTestCases => 'Archive and delete test cases',
            self::AssignTestCases => 'Assign test cases to other users',
            self::ManageStatuses => 'Manage test case statuses',
            self::RecordExecutions => 'Record test executions',
            self::ManageJira => 'Connect and disconnect Jira',
        };
    }

    /**
     * The area of the product this permission belongs to. Grouping them makes
     * the screen readable and shows at a glance whether a role reaches into
     * the administration of the project or only into the testing work.
     */
    public function area(): string
    {
        return match ($this) {
            self::ManageAccounts, self::ManageRoles => 'Administration',
            self::ManageRequirements => 'Requirements',
            self::ManageTestCases, self::DeleteTestCases, self::AssignTestCases, self::ManageStatuses => 'Test cases',
            self::RecordExecutions => 'Executions',
            self::ManageJira => 'Integrations',
        };
    }

    /**
     * Whether the permission hands out control of the project itself rather
     * than of the testing work.
     */
    public function isAdministrative(): bool
    {
        return $this->area() === 'Administration';
    }

    /**
     * Permissions that must already be granted for this one to make sense.
     * Managing roles without seeing the accounts they apply to leaves the
     * holder editing in the dark; deleting a case one cannot edit is the
     * same kind of gap.
     *
     * @return array<int, self>
     */
    public function requires(): array
    {
        return match ($this) {
            self::ManageRoles => [self::ManageAccounts],
            self::DeleteTestCases => [self::ManageTestCases],
            default => [],
        };
    }

    /**
     * Every permission changes something, so a read-only role holds none of
     * them. Kept as a method rather than a bare constant because new
     * permissions should have to state their side.
     */
    public function isWrite(): bool
    {
        return true;
    }

    /**
     * The set a testing role needs: everything about running the tests, and
     * nothing about who may use the project or what its scope is.
     *
     * @return array<int, string>
     */
    public static function forTesters(): array
    {
        return [
            self::ManageTestCases->value,
            self::DeleteTestCases->value,
            self::AssignTestCases->value,
            self::ManageStatuses->value,
            self::RecordExecutions->value,
        ];
    }

    /**
     * What a developer does here: look at the cases and run them.
     *
     * @return array<int, string>
     */
    public static function forDevelopers(): array
    {
        return [self::RecordExecutions->value];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
