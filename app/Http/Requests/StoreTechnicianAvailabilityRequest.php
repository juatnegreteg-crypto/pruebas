<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'days.*.is_available' => ['required', 'boolean'],
            'days.*.start_time' => ['required_if:days.*.is_available,true', 'date_format:H:i', 'nullable'],
            'days.*.end_time' => ['required_if:days.*.is_available,true', 'date_format:H:i', 'after:days.*.start_time', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.required' => 'La disponibilidad es obligatoria.',
            'days.min' => 'Debe enviar al menos un día.',
            'days.max' => 'No puede enviar más de 7 días.',
            'days.*.day_of_week.required' => 'El día de la semana es obligatorio.',
            'days.*.day_of_week.between' => 'El día de la semana debe estar entre 0 (Lunes) y 6 (Domingo).',
            'days.*.day_of_week.distinct' => 'No puede repetir el mismo día de la semana.',
            'days.*.is_available.required' => 'Debe indicar si está disponible.',
            'days.*.start_time.required_if' => 'La hora de inicio es obligatoria para días disponibles.',
            'days.*.start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm.',
            'days.*.end_time.required_if' => 'La hora de fin es obligatoria para días disponibles.',
            'days.*.end_time.date_format' => 'La hora de fin debe tener el formato HH:mm.',
            'days.*.end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
