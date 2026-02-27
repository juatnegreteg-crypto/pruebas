<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxYear = now()->year + 1;

        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'plate' => ['required', 'string', 'max:20', 'unique:vehicles,plate'],
            'vin' => ['nullable', 'string', 'max:50', 'unique:vehicles,vin'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', "max:{$maxYear}"],
            'type' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:30'],
            'transmission' => ['nullable', 'string', 'max:30'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
