<?php

namespace App\Services\Technicians;

use App\Services\Technicians\Contracts\AgendaCancelAppointmentData;
use App\Services\Technicians\Contracts\AgendaCreateAppointmentData;
use App\Services\Technicians\Contracts\AgendaReassignTechnicianData;
use App\Services\Technicians\Contracts\AgendaRescheduleAppointmentData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\AppointmentReference;
use App\Services\Technicians\Contracts\TechnicianReference;
use RuntimeException;

class InMemoryAgendaClient implements AgendaClient
{
    /**
     * @var array<string, array{
     *     starts_at: string,
     *     ends_at: string,
     *     technician_reference: string|null
     * }>
     */
    private array $appointments = [];

    public function createAppointment(AgendaCreateAppointmentData $data): void
    {
        $appointmentReference = (string) $data->appointmentReference;

        if (isset($this->appointments[$appointmentReference])) {
            throw new RuntimeException('La cita ya fue reservada en el stub de Agenda.');
        }

        $availability = new AgendaSlotAvailabilityData(
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
            excludingAppointmentReference: null,
        );

        if (! $this->isSlotAvailable($availability)) {
            throw new RuntimeException('La franja no está disponible en el stub de Agenda.');
        }

        $this->storeAppointment(
            appointmentReference: $data->appointmentReference,
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
        );
    }

    public function rescheduleAppointment(AgendaRescheduleAppointmentData $data): void
    {
        $appointmentReference = (string) $data->appointmentReference;
        $appointment = $this->appointments[$appointmentReference] ?? null;

        if (! $appointment) {
            throw new RuntimeException('No existe cita para reprogramar en el stub de Agenda.');
        }

        $availability = new AgendaSlotAvailabilityData(
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference ?? $this->technicianReferenceFromAppointment($appointment),
            excludingAppointmentReference: $data->appointmentReference,
        );

        if (! $this->isSlotAvailable($availability)) {
            throw new RuntimeException('La nueva franja no está disponible en el stub de Agenda.');
        }

        $this->storeAppointment(
            appointmentReference: $data->appointmentReference,
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference ?? $this->technicianReferenceFromAppointment($appointment),
        );
    }

    public function reassignTechnician(AgendaReassignTechnicianData $data): void
    {
        $appointmentReference = (string) $data->appointmentReference;
        $appointment = $this->appointments[$appointmentReference] ?? null;

        if (! $appointment) {
            throw new RuntimeException('No existe cita para reasignar técnico en el stub de Agenda.');
        }

        $availability = new AgendaSlotAvailabilityData(
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
            excludingAppointmentReference: $data->appointmentReference,
        );

        if (! $this->isSlotAvailable($availability)) {
            throw new RuntimeException('El técnico no está disponible para la franja en el stub de Agenda.');
        }

        $this->storeAppointment(
            appointmentReference: $data->appointmentReference,
            slotWindow: $data->slotWindow,
            technicianReference: $data->technicianReference,
        );
    }

    public function cancelAppointment(AgendaCancelAppointmentData $data): void
    {
        unset($this->appointments[(string) $data->appointmentReference]);
    }

    public function isSlotAvailable(AgendaSlotAvailabilityData $data): bool
    {
        foreach ($this->appointments as $appointmentReference => $appointment) {
            if (
                $data->excludingAppointmentReference !== null
                && $appointmentReference === (string) $data->excludingAppointmentReference
            ) {
                continue;
            }

            if (
                ! $this->slotOverlaps(
                    startsAt: $data->slotWindow->startsAt->format('Y-m-d H:i:s'),
                    endsAt: $data->slotWindow->endsAt->format('Y-m-d H:i:s'),
                    existingStartsAt: $appointment['starts_at'],
                    existingEndsAt: $appointment['ends_at'],
                )
            ) {
                continue;
            }

            $existingTechnicianReference = $appointment['technician_reference'];

            if ($data->technicianReference === null || $existingTechnicianReference === null) {
                return false;
            }

            if ((string) $data->technicianReference === $existingTechnicianReference) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{
     *     starts_at: string,
     *     ends_at: string,
     *     technician_reference: string|null
     * }>
     */
    public function snapshot(): array
    {
        return $this->appointments;
    }

    private function storeAppointment(AppointmentReference $appointmentReference, AgendaSlotWindowData $slotWindow, ?TechnicianReference $technicianReference): void
    {
        $this->appointments[(string) $appointmentReference] = [
            'starts_at' => $slotWindow->startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $slotWindow->endsAt->format('Y-m-d H:i:s'),
            'technician_reference' => $technicianReference?->value,
        ];
    }

    /**
     * @param  array{technician_reference: string|null}  $appointment
     */
    private function technicianReferenceFromAppointment(array $appointment): ?TechnicianReference
    {
        $reference = $appointment['technician_reference'];

        if ($reference === null) {
            return null;
        }

        return new TechnicianReference($reference);
    }

    private function slotOverlaps(
        string $startsAt,
        string $endsAt,
        string $existingStartsAt,
        string $existingEndsAt,
    ): bool {
        return $startsAt < $existingEndsAt && $existingStartsAt < $endsAt;
    }
}
