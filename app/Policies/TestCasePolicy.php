<?php

namespace App\Policies;

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
     */
    public function update(User $user, TestCase $testCase): bool
    {
        return $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user can delete the test case.
     */
    public function delete(User $user, TestCase $testCase): bool
    {
        return $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user can assign the test case to another user.
     */
    public function assign(User $user, TestCase $testCase): bool
    {
        return $user->hasRole('qa') && $this->belongsToProject($user, $testCase);
    }

    /**
     * Determine whether the user belongs to the project the test case is part of.
     */
    private function belongsToProject(User $user, TestCase $testCase): bool
    {
        return $testCase->project->members()->whereKey($user->id)->exists();
    }
}
