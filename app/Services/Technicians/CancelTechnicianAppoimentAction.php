<?php

namespace App\Services\Technicians;

use App\Enums\AppointmentStatus;
use App\Models\TechnicianAppoiment;
use App\Services\Technicians\Contracts\AgendaCancelAppointmentData;
use App\Services\Technicians\Contracts\AppointmentReference;
use Illuminate\Support\Facades\DB;

class CancelTechnicianAppoimentAction
{
    public function __construct(
        private readonly AdminTechnicianAppoimentFlow $adminAppointmentFlow,
        private readonly AgendaClient $agendaClient,
    ) {}

    public function execute(TechnicianAppoiment $appointment): TechnicianAppoiment
    {
        $this->adminAppointmentFlow->ensureCanCancel($appointment->status);

        DB::transaction(function () use ($appointment): void {
            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
            ]);

            $this->agendaClient->cancelAppointment(new AgendaCancelAppointmentData(
                appointmentReference: AppointmentReference::from($appointment->id),
            ));
        });

        return $appointment->refresh();
    }
}
