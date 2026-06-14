<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers{
    use PasswordValidationRules;

    /**
     * Summary of create
     * @param array $input
     * @return User
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'   => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name'     => $input['first_name'] . ' ' . $input['last_name'],
            'email'    => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
