<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAppointmentAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'technicianId' => ['nullable', 'integer', 'exists:technicians,id'],
            'excludingAppointmentId' => ['nullable', 'integer', 'exists:technician_appoiments,id'],
        ];
    }
}
