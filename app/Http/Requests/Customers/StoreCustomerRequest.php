<?php

namespace App\Http\Requests\Customers;

use App\Enums\DocumentType;
use App\Enums\PartyAddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:255', 'regex:/^[^\d;\'"\\-]*$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'documentType' => ['required', new Enum(DocumentType::class)],
            'documentNumber' => ['required', 'digits_between:8,10', 'unique:customers,document_number'],
            'phoneNumber' => ['nullable', 'digits:10'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'addresses' => ['nullable', 'array'],
            'addresses.*' => ['array'],
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
            'documentType.required' => __('customers.validation.documentType.required'),
            'documentType.enum' => __('customers.validation.documentType.enum'),
            'email.email' => __('customers.validation.email.email'),
            'email.required' => __('customers.validation.email.required'),
            'email.unique' => __('customers.validation.email.unique'),
            'phoneNumber.digits' => __('customers.validation.phoneNumber.digits'),
            'documentNumber.digits_between' => __('customers.validation.documentNumber.digits_between'),
            'documentNumber.required' => __('customers.validation.documentNumber.required'),
            'documentNumber.unique' => __('customers.validation.documentNumber.unique'),
            'fullName.regex' => __('customers.validation.fullName.regex'),
            'fullName.required' => __('customers.validation.fullName.required'),
            'addresses.*.type.required' => __('customers.validation.addresses.*.type.required'),
            'addresses.*.street.required' => __('customers.validation.addresses.*.street.required'),
            'addresses.*.city.required' => __('customers.validation.addresses.*.city.required'),
            'addresses.*.state.required' => __('customers.validation.addresses.*.state.required'),
            'addresses.*.country.required' => __('customers.validation.addresses.*.country.required'),
        ];
    }
}
