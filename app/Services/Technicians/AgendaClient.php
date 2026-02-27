<?php

namespace App\Services\Technicians;

use App\Services\Technicians\Contracts\AgendaCancelAppointmentData;
use App\Services\Technicians\Contracts\AgendaCreateAppointmentData;
use App\Services\Technicians\Contracts\AgendaReassignTechnicianData;
use App\Services\Technicians\Contracts\AgendaRescheduleAppointmentData;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;

interface AgendaClient
{
    public function createAppointment(AgendaCreateAppointmentData $data): void;

    public function rescheduleAppointment(AgendaRescheduleAppointmentData $data): void;

    public function reassignTechnician(AgendaReassignTechnicianData $data): void;

    public function cancelAppointment(AgendaCancelAppointmentData $data): void;

    public function isSlotAvailable(AgendaSlotAvailabilityData $data): bool;
}
