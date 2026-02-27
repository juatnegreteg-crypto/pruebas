<?php

namespace App\Services\Technicians\Contracts;

use RuntimeException;

readonly class AppointmentReference
{
    public function __construct(public string $value)
    {
        if ($this->value === '') {
            throw new RuntimeException('La referencia de cita no puede estar vacía.');
        }
    }

    public static function from(int|string $value): self
    {
        return new self((string) $value);
    }

    public static function fromInt(int $id): self
    {
        return self::from($id);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
