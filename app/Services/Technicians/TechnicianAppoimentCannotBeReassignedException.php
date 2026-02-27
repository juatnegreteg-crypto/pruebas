<?php

namespace App\Services\Technicians;

use App\Exceptions\Contracts\DomainErrorCodeException;
use App\Exceptions\Contracts\TranslatableDomainException;
use RuntimeException;
use Throwable;

class TechnicianAppoimentCannotBeReassignedException extends RuntimeException implements DomainErrorCodeException, TranslatableDomainException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'La cita no permite reasignar técnico en su estado actual.',
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return 'APPOINTMENT_CANNOT_REASSIGN_TECHNICIAN';
    }

    public function translationKey(): string
    {
        return 'domain.appointments.cannot_reassign_technician';
    }

    public function translationContext(): array
    {
        return $this->context;
    }
}
