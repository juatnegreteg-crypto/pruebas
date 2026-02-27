<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('users.create');
    }

    protected function prepareForValidation(): void
    {
        $fullName = trim((string) $this->input('full_name'));
        $username = trim((string) $this->input('username'));

        $this->merge([
            'username' => $username !== '' ? $username : null,
            'full_name' => $fullName !== '' ? $fullName : null,
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
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
