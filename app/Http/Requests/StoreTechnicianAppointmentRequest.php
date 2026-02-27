<?php

namespace App\Http\Requests;

use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\TechnicianReference;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'technician' => ['nullable', 'array'],
            'technician.id' => ['required_with:technician', 'integer', 'exists:technicians,id'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function slotWindow(): AgendaSlotWindowData
    {
        $data = $this->validated();

        return new AgendaSlotWindowData(
            startsAt: CarbonImmutable::parse($data['startsAt']),
            endsAt: CarbonImmutable::parse($data['endsAt']),
        );
    }

    public function technicianReference(): ?TechnicianReference
    {
        $technicianId = data_get($this->validated(), 'technician.id');

        if ($technicianId === null) {
            return null;
        }

        return new TechnicianReference((string) $technicianId);
    }
}
