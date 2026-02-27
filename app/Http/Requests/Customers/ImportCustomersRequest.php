<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class ImportCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                // 20MB max. Laravel's `max` for files is in kilobytes.
                'max:20480',
                'mimes:xlsx,xls,csv',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El archivo no es válido.',
            'file.max' => 'El archivo no debe superar los 20MB.',
            'file.mimes' => 'El archivo debe ser de tipo xlsx, xls o csv.',
        ];
    }
}
