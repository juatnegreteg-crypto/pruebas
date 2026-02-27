<?php

namespace App\Services\Technicians\Contracts;

readonly class AgendaRescheduleAppointmentData
{
    public function __construct(
        public AppointmentReference $appointmentReference,
        public AgendaSlotWindowData $slotWindow,
        public ?TechnicianReference $technicianReference = null,
    ) {}
}
