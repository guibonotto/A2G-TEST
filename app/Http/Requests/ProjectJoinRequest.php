<?php

namespace App\Http\Requests;

use App\Concerns\ProjectValidationRules;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class ProjectJoinRequest extends FormRequest
{
    use ProjectValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => $this->projectUuidRules(),
            'password' => $this->projectJoinPasswordRules(),
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $project = Project::where('uuid', $this->string('uuid'))->first();

                if ($project === null) {
                    return;
                }

                if (! Hash::check((string) $this->input('password'), $project->password)) {
                    $validator->errors()->add('password', __('A senha informada está incorreta.'));

                    return;
                }

                if ($project->members()->whereKey($this->user()->id)->exists()) {
                    $validator->errors()->add('uuid', __('Você já faz parte deste projeto.'));
                }
            },
        ];
    }

    /**
     * Get the project the user is attempting to join.
     */
    public function project(): Project
    {
        return Project::where('uuid', $this->string('uuid'))->firstOrFail();
    }
}
