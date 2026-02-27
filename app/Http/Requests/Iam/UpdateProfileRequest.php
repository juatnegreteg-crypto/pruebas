<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('profiles.update');
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $slug = trim((string) $this->input('slug'));

        $this->merge([
            'name' => $name,
            'slug' => $slug !== '' ? str($slug)->slug()->toString() : str($name)->slug()->toString(),
        ]);
    }

    public function rules(): array
    {
        /** @var Profile $profile */
        $profile = $this->route('profile');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($profile->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($profile->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_technician_profile' => ['sometimes', 'boolean'],
        ];
    }
}
