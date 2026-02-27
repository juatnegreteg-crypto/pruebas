<?php

namespace App\Services\Technicians\Contracts;

use RuntimeException;

readonly class TechnicianReference
{
    public function __construct(public string $value)
    {
        if ($this->value === '') {
            throw new RuntimeException('La referencia de técnico no puede estar vacía.');
        }
    }

    public static function fromInt(int $id): self
    {
        return new self((string) $id);
    }

    public function toInt(): int
    {
        if (! ctype_digit($this->value)) {
            throw new RuntimeException("Referencia de técnico inválida para persistencia: {$this->value}.");
        }

        return (int) $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
