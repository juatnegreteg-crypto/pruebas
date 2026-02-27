<?php

namespace App\Services\Technicians\Contracts;

readonly class AgendaCancelAppointmentData
{
    public function __construct(public AppointmentReference $appointmentReference) {}
}
