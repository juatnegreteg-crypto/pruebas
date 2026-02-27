<?php

namespace App\Services\Technicians;

use App\Models\TechnicianAppoiment;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\TechnicianReference;

class TechnicianAppoimentConflictService
{
    public function ensureSlotIsAvailable(
        AgendaSlotWindowData $slotWindow,
        ?TechnicianReference $technicianReference = null,
        ?int $excludingAppointmentId = null,
    ): void {
        $technicianId = $technicianReference?->toInt();

        $hasConflict = TechnicianAppoiment::query()
            ->thatAreNotCancelled()
            ->thatAreOverlappingWithSlot($slotWindow->startsAt, $slotWindow->endsAt)
            ->when($excludingAppointmentId !== null, fn ($query) => $query->whereKeyNot($excludingAppointmentId))
            ->where(function ($query) use ($technicianId) {
                if ($technicianId === null) {
                    $query->whereNull('technician_id');

                    return;
                }

                $query->whereNull('technician_id')
                    ->orWhere('technician_id', $technicianId);
            })
            ->exists();

        if ($hasConflict) {
            throw new TechnicianAppoimentSlotUnavailableException('La franja seleccionada no está disponible.');
        }
    }
}
