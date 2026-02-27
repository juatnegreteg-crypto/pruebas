<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $rateInput = $this->input('rate');
        $rateString = trim((string) $rateInput);
        $codeBase = $rateString !== '' ? "{$name}-{$rateString}" : $name;

        $this->merge([
            'name' => $name,
            'rate' => $rateInput,
            'code' => str($codeBase)->slug()->upper()->toString(),
            'jurisdiction' => 'Colombia',
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:taxes,code'],
            'jurisdiction' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
