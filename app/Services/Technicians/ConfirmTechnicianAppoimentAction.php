<?php

namespace App\Services\Technicians;

use App\Enums\AppointmentStatus;
use App\Models\TechnicianAppoiment;
use Illuminate\Support\Facades\DB;

class ConfirmTechnicianAppoimentAction
{
    public function __construct(
        private readonly AdminTechnicianAppoimentFlow $adminAppointmentFlow,
    ) {}

    public function execute(TechnicianAppoiment $appointment): TechnicianAppoiment
    {
        $this->adminAppointmentFlow->ensureCanConfirm($appointment->status);

        if ($appointment->status?->matches(AppointmentStatus::Confirmed) === true) {
            return $appointment;
        }

        DB::transaction(function () use ($appointment): void {
            $appointment->update([
                'status' => AppointmentStatus::Confirmed,
            ]);
        });

        return $appointment->refresh();
    }
}
