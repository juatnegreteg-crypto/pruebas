<?php

namespace App\Exceptions\Registrars;

use App\Exceptions\Contracts\ExceptionHttpMappingRegistrar;
use App\Exceptions\ExceptionMapper;
use App\Services\Technicians\TechnicianAppoimentCannotBeCancelledException;
use App\Services\Technicians\TechnicianAppoimentCannotBeConfirmedException;
use App\Services\Technicians\TechnicianAppoimentCannotBeReassignedException;
use App\Services\Technicians\TechnicianAppoimentCannotBeRescheduledException;
use App\Services\Technicians\TechnicianAppoimentSlotUnavailableException;

class AppointmentsExceptionMappingRegistrar implements ExceptionHttpMappingRegistrar
{
    public function register(ExceptionMapper $mapper): void
    {
        $mapper->registerValidation(
            TechnicianAppoimentCannotBeRescheduledException::class,
            field: 'appointment',
            statusCode: 422,
            report: false,
        );

        $mapper->registerValidation(
            TechnicianAppoimentSlotUnavailableException::class,
            field: 'slot',
            statusCode: 422,
            report: false,
        );

        $mapper->registerValidation(
            TechnicianAppoimentCannotBeReassignedException::class,
            field: 'technician',
            statusCode: 422,
            report: false,
        );

        $mapper->registerValidation(
            TechnicianAppoimentCannotBeCancelledException::class,
            field: 'appointment',
            statusCode: 422,
            report: false,
        );

        $mapper->registerValidation(
            TechnicianAppoimentCannotBeConfirmedException::class,
            field: 'appointment',
            statusCode: 422,
            report: false,
        );
    }
}
