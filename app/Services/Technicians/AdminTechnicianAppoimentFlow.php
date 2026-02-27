<?php

namespace App\Services\Technicians;

use App\Enums\AppointmentStatus;
use InvalidArgumentException;

class AdminTechnicianAppoimentFlow
{
    public function statusOnCreation(): AppointmentStatus
    {
        return AppointmentStatus::Pending;
    }

    public function canReschedule(AppointmentStatus|string $status): bool
    {
        $status = $this->resolveStatus($status);

        return in_array($status, [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
        ], true);
    }

    public function canConfirm(AppointmentStatus|string $status): bool
    {
        return $this->resolveStatus($status) === AppointmentStatus::Pending;
    }

    public function ensureCanConfirm(AppointmentStatus|string $status): void
    {
        if (! $this->canConfirm($status)) {
            throw new TechnicianAppoimentCannotBeConfirmedException;
        }
    }

    public function ensureCanReschedule(AppointmentStatus|string $status): void
    {
        if (! $this->canReschedule($status)) {
            throw new TechnicianAppoimentCannotBeRescheduledException(
                'La cita no puede reprogramarse en su estado actual.'
            );
        }
    }

    public function canCancel(AppointmentStatus|string $status): bool
    {
        return $this->canReschedule($status);
    }

    public function ensureCanCancel(AppointmentStatus|string $status): void
    {
        if (! $this->canCancel($status)) {
            throw new TechnicianAppoimentCannotBeCancelledException;
        }
    }

    public function canReassignTechnician(AppointmentStatus|string $status): bool
    {
        return $this->canReschedule($status);
    }

    public function ensureCanReassignTechnician(AppointmentStatus|string $status): void
    {
        if (! $this->canReassignTechnician($status)) {
            throw new TechnicianAppoimentCannotBeReassignedException(
                'La cita no permite reasignar técnico en su estado actual.'
            );
        }
    }

    public function isTerminal(AppointmentStatus|string $status): bool
    {
        return $this->resolveStatus($status) === AppointmentStatus::Cancelled;
    }

    /**
     * @return array{create: bool, reschedule: bool, confirm: bool, cancel: bool}
     */
    public function allowedActions(AppointmentStatus|string|null $status): array
    {
        if ($status === null) {
            return [
                'create' => true,
                'reschedule' => false,
                'confirm' => false,
                'cancel' => false,
            ];
        }

        return [
            'create' => false,
            'reschedule' => $this->canReschedule($status),
            'confirm' => $this->canConfirm($status),
            'cancel' => $this->canCancel($status),
        ];
    }

    private function resolveStatus(AppointmentStatus|string $status): AppointmentStatus
    {
        if ($status instanceof AppointmentStatus) {
            return $status;
        }

        return AppointmentStatus::tryFrom($status)
            ?? throw new InvalidArgumentException("Estado de cita inválido: {$status}");
    }
}
