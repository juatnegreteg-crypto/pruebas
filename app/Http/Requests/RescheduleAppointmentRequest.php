<?php

namespace App\Http\Requests;

use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
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
}
