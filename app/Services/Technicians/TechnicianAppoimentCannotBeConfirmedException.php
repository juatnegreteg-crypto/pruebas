<?php

namespace App\Services\Technicians;

use App\Exceptions\Contracts\DomainErrorCodeException;
use App\Exceptions\Contracts\TranslatableDomainException;
use RuntimeException;
use Throwable;

class TechnicianAppoimentCannotBeConfirmedException extends RuntimeException implements DomainErrorCodeException, TranslatableDomainException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'La cita no permite confirmación en su estado actual.',
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return 'APPOINTMENT_CANNOT_CONFIRM';
    }

    public function translationKey(): string
    {
        return 'domain.appointments.cannot_confirm';
    }

    public function translationContext(): array
    {
        return $this->context;
    }
}
