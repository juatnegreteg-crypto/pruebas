<?php

namespace App\Services\Technicians\Contracts;

readonly class AgendaSlotAvailabilityData
{
    public function __construct(
        public AgendaSlotWindowData $slotWindow,
        public ?TechnicianReference $technicianReference = null,
        public ?AppointmentReference $excludingAppointmentReference = null,
    ) {}
}
