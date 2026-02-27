<?php

namespace App\Services\Technicians;

use App\Models\TechnicianAppoiment;
use App\Services\ScheduleService;
use App\Services\Technicians\Contracts\AgendaRescheduleAppointmentData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\AppointmentReference;
use App\Services\Technicians\Contracts\TechnicianReference;
use Illuminate\Support\Facades\DB;

class RescheduleTechnicianAppoimentAction
{
    public function __construct(
        private readonly AdminTechnicianAppoimentFlow $adminAppointmentFlow,
        private readonly TechnicianAppoimentConflictService $appointmentConflictService,
        private readonly AgendaClient $agendaClient,
        private readonly ScheduleService $scheduleService,
    ) {}

    public function execute(TechnicianAppoiment $appointment, AgendaSlotWindowData $slotWindow): TechnicianAppoiment
    {
        $this->adminAppointmentFlow->ensureCanReschedule($appointment->status);

        if (! $this->scheduleService->containsSlot($slotWindow->startsAt, $slotWindow->endsAt)) {
            throw new TechnicianAppoimentSlotUnavailableException('La franja seleccionada no está disponible.');
        }

        $technicianId = $appointment->technician_id;
        $technicianReference = $technicianId !== null ? TechnicianReference::fromInt($technicianId) : null;

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

        DB::transaction(function () use ($appointment, $slotWindow, $technicianReference): void {
            $appointment->update([
                'starts_at' => $slotWindow->startsAt,
                'ends_at' => $slotWindow->endsAt,
            ]);

            $this->agendaClient->rescheduleAppointment(new AgendaRescheduleAppointmentData(
                appointmentReference: AppointmentReference::from($appointment->id),
                slotWindow: $slotWindow,
                technicianReference: $technicianReference,
            ));
        });

        return $appointment->refresh();
    }
}
