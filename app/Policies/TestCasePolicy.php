<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\TestCase;
use App\Models\User;

class TestCasePolicy
{
    /**
     * Determine whether the user can view the test case.
     */
    public function view(User $user, TestCase $testCase): bool
    {
        return $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user can update the test case.
     *
     * Checked against the permission rather than against "is not a viewer",
     * so a project can decide which of its roles writes test cases without
     * the rule being hard-coded to a role name.
     */
    public function update(User $user, TestCase $testCase): bool
    {
        return $user->hasPermission(Permission::ManageTestCases, $testCase->project)
            && $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user can archive or delete the test case.
     */
    public function delete(User $user, TestCase $testCase): bool
    {
        return $user->hasPermission(Permission::DeleteTestCases, $testCase->project)
            && $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user can record an execution of the test case.
     */
    public function execute(User $user, TestCase $testCase): bool
    {
        return $user->hasPermission(Permission::RecordExecutions, $testCase->project)
            && $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user can assign the test case to another user.
     */
    public function assign(User $user, TestCase $testCase): bool
    {
        return $user->hasPermission(Permission::AssignTestCases, $testCase->project)
            && $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user belongs to the project the test case is part of.
     */
    private function belongsToProject(User $user, TestCase $testCase): bool
    {
        return $testCase->project->members()->whereKey($user->id)->exists();
    }
}
