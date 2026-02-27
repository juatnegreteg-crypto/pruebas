<?php

namespace App\Services\Technicians\Contracts;

use DateTimeInterface;
use InvalidArgumentException;

readonly class AgendaSlotWindowData
{
    public function __construct(
        public DateTimeInterface $startsAt,
        public DateTimeInterface $endsAt,
    ) {
        if ($this->startsAt >= $this->endsAt) {
            throw new InvalidArgumentException('La franja de cita debe tener un inicio menor que el fin.');
        }
    }
}
