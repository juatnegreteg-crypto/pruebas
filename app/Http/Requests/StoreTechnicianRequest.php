<?php

namespace App\Http\Requests;

use App\Enums\PartyAddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => $this->email !== null ? strtolower(trim((string) $this->email)) : null,
            'phone' => $this->phone ? trim((string) $this->phone) : null,
            'addresses' => $this->normalizeAddresses($this->addresses),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\d+$/', 'max:20'],
            'isActive' => ['sometimes', 'boolean'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => ['nullable', 'integer'],
            'addresses.*.type' => ['required', new Enum(PartyAddressType::class)],
            'addresses.*.isPrimary' => ['sometimes', 'boolean'],
            'addresses.*.street' => ['required', 'string', 'max:255'],
            'addresses.*.complement' => ['nullable', 'string', 'max:255'],
            'addresses.*.neighborhood' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required', 'string', 'max:255'],
            'addresses.*.state' => ['required', 'string', 'max:255'],
            'addresses.*.postalCode' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['required', 'string', 'max:255'],
            'addresses.*.reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'phone.regex' => 'El teléfono debe contener solo números.',
            'phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'isActive.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function normalizeAddresses(mixed $addresses): ?array
    {
        if ($addresses === null) {
            return null;
        }

        if (! is_array($addresses)) {
            return [];
        }

        return array_map(function (array $address): array {
            $country = $address['country'] ?? null;
            $country = $country !== null ? trim((string) $country) : '';

            return [
                'id' => $address['id'] ?? null,
                'type' => $address['type'] ?? null,
                'isPrimary' => $address['isPrimary'] ?? false,
                'street' => $this->trimOrNull($address['street'] ?? null),
                'complement' => $this->trimOrNull($address['complement'] ?? null),
                'neighborhood' => $this->trimOrNull($address['neighborhood'] ?? null),
                'city' => $this->trimOrNull($address['city'] ?? null),
                'state' => $this->trimOrNull($address['state'] ?? null),
                'postalCode' => $this->trimOrNull($address['postalCode'] ?? null),
                'country' => $country !== '' ? $country : 'Colombia',
                'reference' => $this->trimOrNull($address['reference'] ?? null),
            ];
        }, $addresses);
    }

    private function trimOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
