<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

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
}
