<?php

namespace App\Services\Technicians;

use App\Enums\AppointmentStatus;
use App\Models\TechnicianAppoiment;
use App\Services\Technicians\Contracts\AgendaCancelAppointmentData;
use App\Services\Technicians\Contracts\AgendaCreateAppointmentData;
use App\Services\Technicians\Contracts\AgendaReassignTechnicianData;
use App\Services\Technicians\Contracts\AgendaRescheduleAppointmentData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AppointmentReference;
use RuntimeException;

class DatabaseAgendaClient implements AgendaClient
{
    public function createAppointment(AgendaCreateAppointmentData $data): void
    {
        if (! $this->isSlotAvailable(new AgendaSlotAvailabilityData(
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
            excludingAppointmentReference: $data->appointmentReference,
        ))) {
            throw new TechnicianAppoimentSlotUnavailableException;
        }

        $appointment = $this->findAppointmentOrFail($data->appointmentReference);

        $appointment->update([
            'starts_at' => $data->slotWindow->startsAt,
            'ends_at' => $data->slotWindow->endsAt,
            'technician_id' => $data->technicianReference?->toInt(),
        ]);
    }

    public function rescheduleAppointment(AgendaRescheduleAppointmentData $data): void
    {
        if (! $this->isSlotAvailable(new AgendaSlotAvailabilityData(
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
            excludingAppointmentReference: $data->appointmentReference,
        ))) {
            throw new TechnicianAppoimentSlotUnavailableException;
        }

        $appointment = $this->findAppointmentOrFail($data->appointmentReference);

        $appointment->update([
            'starts_at' => $data->slotWindow->startsAt,
            'ends_at' => $data->slotWindow->endsAt,
            'technician_id' => $data->technicianReference !== null
                ? $data->technicianReference->toInt()
                : $appointment->technician_id,
        ]);
    }

    public function reassignTechnician(AgendaReassignTechnicianData $data): void
    {
        if (! $this->isSlotAvailable(new AgendaSlotAvailabilityData(
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
            excludingAppointmentReference: $data->appointmentReference,
        ))) {
            throw new TechnicianAppoimentSlotUnavailableException;
        }

        $appointment = $this->findAppointmentOrFail($data->appointmentReference);

        $appointment->update([
            'technician_id' => $data->technicianReference->toInt(),
        ]);
    }

    public function cancelAppointment(AgendaCancelAppointmentData $data): void
    {
        $appointment = $this->findAppointmentOrFail($data->appointmentReference);

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
        ]);
    }

    public function isSlotAvailable(AgendaSlotAvailabilityData $data): bool
    {
        $excludingAppointmentId = $data->excludingAppointmentReference !== null
            ? $this->resolveAppointmentId($data->excludingAppointmentReference)
            : null;

        $hasConflict = TechnicianAppoiment::query()
            ->thatAreNotCancelled()
            ->thatAreOverlappingWithSlot($data->slotWindow->startsAt, $data->slotWindow->endsAt)
            ->when($excludingAppointmentId !== null, function ($query) use ($excludingAppointmentId) {
                $query->whereKeyNot($excludingAppointmentId);
            })
            ->where(function ($query) use ($data) {
                if ($data->technicianReference === null) {
                    $query->whereNull('technician_id');

                    return;
                }

                $technicianId = $data->technicianReference->toInt();

                $query->whereNull('technician_id')
                    ->orWhere('technician_id', $technicianId);
            })
            ->exists();

        return ! $hasConflict;
    }

    private function findAppointmentOrFail(AppointmentReference $appointmentReference): TechnicianAppoiment
    {
        $appointmentId = $this->resolveAppointmentId($appointmentReference);
        $appointment = TechnicianAppoiment::query()->find($appointmentId);

        if (! $appointment) {
            throw new RuntimeException("No existe cita para referencia {$appointmentReference}.");
        }

        return $appointment;
    }

    private function resolveAppointmentId(AppointmentReference $appointmentReference): int
    {
        if (! ctype_digit($appointmentReference->value)) {
            throw new RuntimeException("Referencia de cita inválida para persistencia: {$appointmentReference->value}.");
        }

        return (int) $appointmentReference->value;
    }
}
