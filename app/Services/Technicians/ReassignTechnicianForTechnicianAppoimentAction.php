<?php

namespace App\Services\Technicians;

use App\Models\TechnicianAppoiment;
use App\Services\Technicians\Contracts\AgendaReassignTechnicianData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\AppointmentReference;
use App\Services\Technicians\Contracts\TechnicianReference;
use Illuminate\Support\Facades\DB;

class ReassignTechnicianForTechnicianAppoimentAction
{
    public function __construct(
        private readonly AdminTechnicianAppoimentFlow $adminAppointmentFlow,
        private readonly TechnicianAppoimentConflictService $appointmentConflictService,
        private readonly AgendaClient $agendaClient,
    ) {}

    public function execute(TechnicianAppoiment $appointment, TechnicianReference $technicianReference): TechnicianAppoiment
    {
        $this->adminAppointmentFlow->ensureCanReassignTechnician($appointment->status);

        if (
            $appointment->technician_id !== null
            && (string) $appointment->technician_id === $technicianReference->value
        ) {
            return $appointment->refresh();
        }

        $slotWindow = new AgendaSlotWindowData(
            startsAt: $appointment->starts_at,
            endsAt: $appointment->ends_at,
        );

        $this->appointmentConflictService->ensureSlotIsAvailable(
            slotWindow: $slotWindow,
            technicianReference: $technicianReference,
            excludingAppointmentId: $appointment->id,
        );

        $isAvailable = $this->agendaClient->isSlotAvailable(new AgendaSlotAvailabilityData(
            slotWindow: $slotWindow,
            technicianReference: $technicianReference,
            excludingAppointmentReference: AppointmentReference::from($appointment->id),
        ));

        if (! $isAvailable) {
            throw new TechnicianAppoimentSlotUnavailableException('La franja seleccionada no está disponible.');
        }

        $technicianId = $technicianReference->toInt();

        DB::transaction(function () use ($appointment, $slotWindow, $technicianId, $technicianReference): void {
            $appointment->update([
                'technician_id' => $technicianId,
            ]);

            $this->agendaClient->reassignTechnician(new AgendaReassignTechnicianData(
                appointmentReference: AppointmentReference::from($appointment->id),
                slotWindow: $slotWindow,
                technicianReference: $technicianReference,
            ));
        });

        return $appointment->refresh();
    }
}
