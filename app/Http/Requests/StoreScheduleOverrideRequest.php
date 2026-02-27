<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today', 'unique:schedule_overrides,date'],
            'is_working_day' => ['required', 'boolean'],
            'start_time' => ['required_if:is_working_day,true', 'date_format:H:i', 'nullable'],
            'end_time' => ['required_if:is_working_day,true', 'date_format:H:i', 'after:start_time', 'nullable'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'date.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'date.unique' => 'Ya existe una excepción para esta fecha.',
            'is_working_day.required' => 'Debe indicar si es día laborable.',
            'start_time.required_if' => 'La hora de inicio es obligatoria para días laborables.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm.',
            'end_time.required_if' => 'La hora de fin es obligatoria para días laborables.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:mm.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ];
    }
}
