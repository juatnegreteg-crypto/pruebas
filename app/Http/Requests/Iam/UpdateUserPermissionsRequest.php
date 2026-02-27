<?php

namespace App\Http\Requests\Iam;

use App\Http\Requests\Concerns\AuthorizesPermission;
use App\Services\Iam\PermissionPresetResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPermissionsRequest extends FormRequest
{
    use AuthorizesPermission;

    public function authorize(): bool
    {
        return $this->authorizesPermission('profiles.assign_permissions');
    }

    public function rules(): array
    {
        $capabilityKeys = app(PermissionPresetResolver::class)->capabilityKeys();

        return [
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
            'selected_capability_keys' => ['sometimes', 'array'],
            'selected_capability_keys.*' => ['string', Rule::in($capabilityKeys)],
        ];
    }
}
