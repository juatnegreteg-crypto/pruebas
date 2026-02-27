<?php

namespace App\Http\Requests;

use App\Enums\UnitOfMeasure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'unit' => ['nullable', new Enum(UnitOfMeasure::class)],
            'isActive' => ['nullable', 'boolean'],
            'taxes' => ['nullable', 'array'],
            'taxes.*.taxId' => ['required', 'integer', 'exists:taxes,id'],
            'taxes.*.rate' => ['required', 'numeric', 'min:0'],
            'taxes.*.startAt' => ['required', 'date'],
            'taxes.*.endAt' => ['nullable', 'date', 'after_or_equal:taxes.*.startAt'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $currency = $this->input('currency');
        $unit = $this->input('unit');
        $payload = [];

        if ($this->has('currency')) {
            $currency = $currency !== null ? strtoupper(trim((string) $currency)) : null;
            $payload['currency'] = $currency !== '' ? $currency : null;
        }

        if ($this->has('unit')) {
            $unit = $unit !== null ? trim((string) $unit) : null;
            $payload['unit'] = $unit !== '' ? $unit : null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
