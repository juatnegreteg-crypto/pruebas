<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'days.*.is_working_day' => ['required', 'boolean'],
            'days.*.start_time' => ['required_if:days.*.is_working_day,true', 'date_format:H:i', 'nullable'],
            'days.*.end_time' => ['required_if:days.*.is_working_day,true', 'date_format:H:i', 'after:days.*.start_time', 'nullable'],
            'days.*.slot_duration' => ['required_if:days.*.is_working_day,true', 'integer', 'min:5', 'max:480', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.required' => 'La configuración semanal es obligatoria.',
            'days.size' => 'Debe enviar exactamente 7 días.',
            'days.*.day_of_week.required' => 'El día de la semana es obligatorio.',
            'days.*.day_of_week.between' => 'El día de la semana debe estar entre 0 (Lunes) y 6 (Domingo).',
            'days.*.day_of_week.distinct' => 'No se pueden repetir días de la semana.',
            'days.*.is_working_day.required' => 'Debe indicar si es día laborable.',
            'days.*.start_time.required_if' => 'La hora de inicio es obligatoria para días laborables.',
            'days.*.start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm.',
            'days.*.end_time.required_if' => 'La hora de fin es obligatoria para días laborables.',
            'days.*.end_time.date_format' => 'La hora de fin debe tener el formato HH:mm.',
            'days.*.end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'days.*.slot_duration.required_if' => 'La duración del turno es obligatoria para días laborables.',
            'days.*.slot_duration.min' => 'La duración mínima del turno es 5 minutos.',
            'days.*.slot_duration.max' => 'La duración máxima del turno es 480 minutos.',
        ];
    }
}
