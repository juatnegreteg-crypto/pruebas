<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_full_day' => ['required', 'boolean'],
            'start_time' => ['required_if:is_full_day,false', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:is_full_day,false', 'nullable', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'start_date.after_or_equal' => 'La fecha de inicio debe ser hoy o posterior.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'is_full_day.required' => 'Debe indicar si es día completo.',
            'is_full_day.boolean' => 'El campo día completo debe ser verdadero o falso.',
            'start_time.required_if' => 'La hora de inicio es obligatoria cuando no es día completo.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm.',
            'end_time.required_if' => 'La hora de fin es obligatoria cuando no es día completo.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:mm.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('start_date') && $this->filled('end_date')) {
                $start = Carbon::parse($this->input('start_date'));
                $end = Carbon::parse($this->input('end_date'));

                if ((int) $start->diffInDays($end) > 365) {
                    $validator->errors()->add('end_date', 'El rango de fechas no puede exceder 365 días.');
                }
            }
        });
    }
}
