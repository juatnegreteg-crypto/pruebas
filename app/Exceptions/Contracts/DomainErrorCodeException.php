<?php

namespace App\Exceptions\Contracts;

interface DomainErrorCodeException
{
    public function errorCode(): string;
}
