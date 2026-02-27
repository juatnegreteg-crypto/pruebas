<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileSkillsRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('skills.assign');
    }

    public function rules(): array
    {
        return [
            'skill_ids' => ['required', 'array'],
            'skill_ids.*' => ['integer', Rule::exists('skills', 'id')],
        ];
    }
}
