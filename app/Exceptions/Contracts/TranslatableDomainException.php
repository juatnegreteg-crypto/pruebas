<?php

namespace App\Exceptions\Contracts;

interface TranslatableDomainException
{
    public function translationKey(): string;

    /**
     * @return array<string, mixed>
     */
    public function translationContext(): array;
}
