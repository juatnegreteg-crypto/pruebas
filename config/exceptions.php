<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exception Mapping Registrars
    |--------------------------------------------------------------------------
    |
    | Class-strings that register HTTP mapping/reporting rules in
    | App\Exceptions\ExceptionMapper. Each class must implement
    | App\Exceptions\Contracts\ExceptionHttpMappingRegistrar.
    |
    */
    'mapping_registrars' => [
        App\Exceptions\Registrars\AppointmentsExceptionMappingRegistrar::class,
    ],
];
