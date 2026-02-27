<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('users.update');
    }

    public function rules(): array
    {
        return [
            'profile_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
        ];
    }
}
