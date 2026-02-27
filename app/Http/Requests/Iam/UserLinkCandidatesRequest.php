<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use Illuminate\Foundation\Http\FormRequest;

class UserLinkCandidatesRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('users.create');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => ($email = strtolower(trim((string) $this->query('email', '')))) !== '' ? $email : null,
            'name' => ($name = trim((string) $this->query('name', ''))) !== '' ? $name : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255', 'min:2'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $email = $this->input('email');
            $name = $this->input('name');

            if ($email === null && $name === null) {
                $validator->errors()->add('email', 'Debes enviar email o name para buscar candidatos.');
            }
        });
    }
}
