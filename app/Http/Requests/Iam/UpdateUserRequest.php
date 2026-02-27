<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('users.update');
    }

    protected function prepareForValidation(): void
    {
        $fullName = trim((string) $this->input('full_name'));

        $this->merge([
            'username' => trim((string) $this->input('username')),
            'full_name' => $fullName !== '' ? $fullName : null,
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'skill_ids' => ['sometimes', 'array'],
            'skill_ids.*' => ['integer', Rule::exists('skills', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Este valor ya está en uso.',
            'email.unique' => 'Este valor ya está en uso.',
        ];
    }
}
