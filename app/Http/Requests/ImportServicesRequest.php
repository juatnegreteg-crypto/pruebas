<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates file upload for service imports.
 *
 * Responsibilities:
 * - File is required
 * - Extension: xls, xlsx
 * - Max size: 10MB (10240 KB)
 *
 * Structure validation (headers/duplicates/order/data rows) is handled by ServiceImportService::validateFile()
 *
 * @codeCoverageIgnore
 */
class ImportServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xls,xlsx,ods', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debe proporcionar un archivo para importar.',
            'file.file' => 'El archivo proporcionado no es válido.',
            'file.mimes' => 'El archivo debe ser de tipo: xls, xlsx, ods.',
            'file.max' => 'El archivo no debe exceder los 10 MB.',
        ];
    }
}
