<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait ProjectValidationRules
{
    /**
     * Get the validation rules used to validate a project's name.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function projectNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate a new project's password.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function projectPasswordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /**
     * Get the validation rules used to validate the project id supplied to join a project.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function projectUuidRules(): array
    {
        return ['required', 'string', 'uuid', 'exists:projects,uuid'];
    }

    /**
     * Get the validation rules used to validate the password supplied to join a project.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function projectJoinPasswordRules(): array
    {
        return ['required', 'string'];
    }
}
