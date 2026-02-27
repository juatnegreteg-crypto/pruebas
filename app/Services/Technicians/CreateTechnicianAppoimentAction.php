<?php

namespace App\Services\Technicians;

use App\Models\TechnicianAppoiment;
use App\Services\ScheduleService;
use App\Services\Technicians\Contracts\AgendaCreateAppointmentData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\AppointmentReference;
use App\Services\Technicians\Contracts\TechnicianReference;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateTechnicianAppoimentAction
{
    public function __construct(
        private readonly AdminTechnicianAppoimentFlow $adminAppointmentFlow,
        private readonly TechnicianAppoimentConflictService $appointmentConflictService,
        private readonly AgendaClient $agendaClient,
        private readonly ScheduleService $scheduleService,
    ) {}

    public function execute(AgendaSlotWindowData $slotWindow, ?TechnicianReference $technicianReference): TechnicianAppoiment
    {
        try {
            return Cache::lock($this->buildSlotLockKey($slotWindow), 5)
                ->block(5, function () use ($slotWindow, $technicianReference): TechnicianAppoiment {
                    if (! $this->scheduleService->containsSlot($slotWindow->startsAt, $slotWindow->endsAt)) {
                        throw new TechnicianAppoimentSlotUnavailableException('La franja seleccionada no está disponible.');
                    }

                    $this->appointmentConflictService->ensureSlotIsAvailable(
                        slotWindow: $slotWindow,
                        technicianReference: $technicianReference,
                    );

                    $isAvailable = $this->agendaClient->isSlotAvailable(new AgendaSlotAvailabilityData(
                        slotWindow: $slotWindow,
                        technicianReference: $technicianReference,
                    ));

                    if (! $isAvailable) {
                        throw new TechnicianAppoimentSlotUnavailableException('La franja seleccionada no está disponible.');
                    }

                    $technicianId = $technicianReference?->toInt();

                    /** @var TechnicianAppoiment $appointment */
                    $appointment = DB::transaction(function () use ($slotWindow, $technicianId, $technicianReference): TechnicianAppoiment {
                        $appointment = TechnicianAppoiment::query()->create([
                            'starts_at' => $slotWindow->startsAt,
                            'ends_at' => $slotWindow->endsAt,
                            'technician_id' => $technicianId,
                            'status' => $this->adminAppointmentFlow->statusOnCreation(),
                        ]);

                        $this->agendaClient->createAppointment(new AgendaCreateAppointmentData(
                            appointmentReference: AppointmentReference::from($appointment->id),
                            slotWindow: $slotWindow,
                            technicianReference: $technicianReference,
                        ));

                        return $appointment;
                    });

                    return $appointment->refresh();
                });
        } catch (LockTimeoutException) {
            throw new TechnicianAppoimentSlotUnavailableException('La franja seleccionada no está disponible.');
        }
    }

    private function buildSlotLockKey(AgendaSlotWindowData $slotWindow): string
    {
        return sprintf(
            'appointments:slot:%s:%s',
            $slotWindow->startsAt->format('YmdHis'),
            $slotWindow->endsAt->format('YmdHis'),
        );
    }
}
