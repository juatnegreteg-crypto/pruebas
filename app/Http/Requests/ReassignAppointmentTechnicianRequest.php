<?php

namespace App\Http\Requests;

use App\Services\Technicians\Contracts\TechnicianReference;
use Illuminate\Foundation\Http\FormRequest;

class ReassignAppointmentTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician' => ['required', 'array'],
            'technician.id' => ['required', 'integer', 'exists:technicians,id'],
        ];
    }

    public function technicianReference(): TechnicianReference
    {
        /** @var int|string $technicianId */
        $technicianId = data_get($this->validated(), 'technician.id');

        return new TechnicianReference((string) $technicianId);
    }
}
