<?php

namespace App\Exceptions\Contracts;

use App\Exceptions\ExceptionMapper;

interface ExceptionHttpMappingRegistrar
{
    public function register(ExceptionMapper $mapper): void;
}
