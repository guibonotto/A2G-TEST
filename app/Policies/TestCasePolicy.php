<?php

namespace App\Policies;

use App\Models\TestCase;
use App\Models\User;

class TestCasePolicy
{
    /**
     * Determine whether the user can assign the test case to another user.
     */
    public function assign(User $user, TestCase $testCase): bool
    {
        return $user->hasRole('qa');
    }
}
