<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetScheduleAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'El parámetro "from" debe tener el formato Y-m-d.',
            'to.date_format' => 'El parámetro "to" debe tener el formato Y-m-d.',
            'to.after_or_equal' => 'El parámetro "to" debe ser igual o posterior a "from".',
        ];
    }
}
