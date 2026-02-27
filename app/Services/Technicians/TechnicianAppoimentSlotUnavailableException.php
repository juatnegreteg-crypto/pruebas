<?php

namespace App\Services\Technicians;

use App\Exceptions\Contracts\DomainErrorCodeException;
use App\Exceptions\Contracts\TranslatableDomainException;
use RuntimeException;
use Throwable;

class TechnicianAppoimentSlotUnavailableException extends RuntimeException implements DomainErrorCodeException, TranslatableDomainException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'La franja seleccionada no está disponible.',
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return 'APPOINTMENT_SLOT_UNAVAILABLE';
    }

    public function translationKey(): string
    {
        return 'domain.appointments.slot_unavailable';
    }

    public function translationContext(): array
    {
        return $this->context;
    }
}
