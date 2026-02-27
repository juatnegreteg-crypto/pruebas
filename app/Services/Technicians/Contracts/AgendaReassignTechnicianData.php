<?php

namespace App\Services\Technicians\Contracts;

readonly class AgendaReassignTechnicianData
{
    public function __construct(
        public AppointmentReference $appointmentReference,
        public AgendaSlotWindowData $slotWindow,
        public TechnicianReference $technicianReference,
    ) {}
}
