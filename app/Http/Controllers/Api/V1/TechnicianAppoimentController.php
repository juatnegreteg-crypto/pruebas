<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmAppointmentRequest;
use App\Http\Requests\GetAppointmentAvailabilityRequest;
use App\Http\Requests\ListAppointmentsRequest;
use App\Http\Requests\ReassignAppointmentTechnicianRequest;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreTechnicianAppointmentRequest;
use App\Http\Requests\StoreTechnicianObservationRequest;
use App\Http\Resources\AppointmentAvailabilityDayResource;
use App\Http\Resources\TechnicianAppoimentResource;
use App\Models\TechnicianAppoiment;
use App\Models\TechnicianBlock;
use App\Services\ObservationService;
use App\Services\ScheduleService;
use App\Services\Technicians\AgendaClient;
use App\Services\Technicians\CancelTechnicianAppoimentAction;
use App\Services\Technicians\ConfirmTechnicianAppoimentAction;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\AppointmentReference;
use App\Services\Technicians\Contracts\TechnicianReference;
use App\Services\Technicians\CreateTechnicianAppoimentAction;
use App\Services\Technicians\ReassignTechnicianForTechnicianAppoimentAction;
use App\Services\Technicians\RescheduleTechnicianAppoimentAction;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class TechnicianAppoimentController extends Controller
{
    public function __construct(
        private readonly CreateTechnicianAppoimentAction $createAppointmentAction,
        private readonly RescheduleTechnicianAppoimentAction $rescheduleAppointmentAction,
        private readonly ReassignTechnicianForTechnicianAppoimentAction $reassignAppointmentTechnicianAction,
        private readonly CancelTechnicianAppoimentAction $cancelAppointmentAction,
        private readonly ConfirmTechnicianAppoimentAction $confirmAppointmentAction,
    ) {}

    public function index(ListAppointmentsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $startsAt = CarbonImmutable::parse($data['startsAt']);
        $endsAt = CarbonImmutable::parse($data['endsAt']);

        $appointments = TechnicianAppoiment::query()
            ->thatAreOverlappingWithSlot($startsAt, $endsAt)
            ->orderBy('starts_at')
            ->get();

        return TechnicianAppoimentResource::collection($appointments)->response();
    }

    public function availability(
        GetAppointmentAvailabilityRequest $request,
        ScheduleService $scheduleService,
        AgendaClient $agendaClient,
    ): JsonResponse {
        $data = $request->validated();

        $from = CarbonImmutable::parse($data['from']);
        $to = CarbonImmutable::parse($data['to']);

        $technicianReference = isset($data['technicianId'])
            ? TechnicianReference::fromInt((int) $data['technicianId'])
            : null;

        $excludingAppointmentReference = isset($data['excludingAppointmentId'])
            ? new AppointmentReference((string) $data['excludingAppointmentId'])
            : null;

        $slotsByDate = $scheduleService->getAvailableSlots($from, $to);
        $technicianBlocks = isset($data['technicianId'])
            ? TechnicianBlock::query()
                ->where('technician_id', (int) $data['technicianId'])
                ->thatAreBetweenDates($from, $to)
                ->get()
            : collect();

        $days = collect($slotsByDate)
            ->map(function (array $slots, string $dateKey) use (
                $agendaClient,
                $technicianReference,
                $excludingAppointmentReference,
                $technicianBlocks,
            ): array {
                $slotResources = array_map(function (array $slot) use (
                    $agendaClient,
                    $technicianReference,
                    $excludingAppointmentReference,
                    $technicianBlocks,
                ): array {
                    $slotWindow = new AgendaSlotWindowData(
                        startsAt: CarbonImmutable::parse($slot['start']),
                        endsAt: CarbonImmutable::parse($slot['end']),
                    );

                    $isBlockedByTechnician = $this->slotIsBlockedByTechnician($slotWindow, $technicianBlocks);
                    $isAvailable = $agendaClient->isSlotAvailable(new AgendaSlotAvailabilityData(
                        slotWindow: $slotWindow,
                        technicianReference: $technicianReference,
                        excludingAppointmentReference: $excludingAppointmentReference,
                    )) && ! $isBlockedByTechnician;

                    return [
                        'start' => $slotWindow->startsAt->toIso8601String(),
                        'end' => $slotWindow->endsAt->toIso8601String(),
                        'isAvailable' => $isAvailable,
                    ];
                }, $slots);

                return [
                    'date' => $dateKey,
                    'slots' => $slotResources,
                ];
            })
            ->sortBy('date')
            ->values();

        return AppointmentAvailabilityDayResource::collection($days)->response();
    }

    private function slotIsBlockedByTechnician(AgendaSlotWindowData $slotWindow, \Illuminate\Support\Collection $technicianBlocks): bool
    {
        if ($technicianBlocks->isEmpty()) {
            return false;
        }

        $slotDate = $slotWindow->startsAt->toDateString();
        $slotStart = $slotWindow->startsAt->format('H:i');
        $slotEnd = $slotWindow->endsAt->format('H:i');

        foreach ($technicianBlocks as $block) {
            $blockStartDate = $block->start_date->toDateString();
            $blockEndDate = $block->end_date->toDateString();

            if ($slotDate < $blockStartDate || $slotDate > $blockEndDate) {
                continue;
            }

            if ($block->is_full_day) {
                return true;
            }

            $blockStart = substr((string) $block->start_time, 0, 5);
            $blockEnd = substr((string) $block->end_time, 0, 5);

            if ($slotStart < $blockEnd && $blockStart < $slotEnd) {
                return true;
            }
        }

        return false;
    }

    public function store(StoreTechnicianAppointmentRequest $request, ObservationService $observationService): JsonResponse
    {
        $appointment = $this->createAppointmentAction->execute(
            $request->slotWindow(),
            $request->technicianReference(),
        );
        $observationService->syncSingleObservation(
            $appointment,
            'appointment_request',
            $request->validated('observation'),
            ['customer'],
            $request->user()?->id,
        );

        return TechnicianAppoimentResource::make($appointment)
            ->response()
            ->setStatusCode(201);
    }

    public function reschedule(RescheduleAppointmentRequest $request, TechnicianAppoiment $appointment): JsonResponse
    {
        $appointment = $this->rescheduleAppointmentAction->execute($appointment, $request->slotWindow());

        return TechnicianAppoimentResource::make($appointment)->response();
    }

    public function reassignTechnician(
        ReassignAppointmentTechnicianRequest $request,
        TechnicianAppoiment $appointment
    ): JsonResponse {
        $appointment = $this->reassignAppointmentTechnicianAction->execute(
            $appointment,
            $request->technicianReference(),
        );

        return TechnicianAppoimentResource::make($appointment)->response();
    }

    public function cancel(TechnicianAppoiment $appointment): JsonResponse
    {
        $appointment = $this->cancelAppointmentAction->execute($appointment);

        return TechnicianAppoimentResource::make($appointment)->response();
    }

    public function confirm(
        ConfirmAppointmentRequest $request,
        TechnicianAppoiment $appointment,
        ObservationService $observationService
    ): JsonResponse {
        $appointment = $this->confirmAppointmentAction->execute($appointment);
        $adminObservation = $request->validated('adminObservation');
        $shareCustomerObservation = (bool) $request->validated('shareCustomerObservation');

        if ($adminObservation !== null) {
            $observationService->syncSingleObservation(
                $appointment,
                'appointment_confirmation',
                $adminObservation,
                ['admin'],
                $request->user()?->id,
            );
        }

        if ($shareCustomerObservation) {
            $observationService->addAudienceTagToLatest(
                $appointment,
                'appointment_request',
                'technician',
            );
        }

        return TechnicianAppoimentResource::make($appointment)->response();
    }

    public function addTechnicianObservation(
        StoreTechnicianObservationRequest $request,
        TechnicianAppoiment $appointment,
        ObservationService $observationService
    ): JsonResponse {
        $observationService->syncSingleObservation(
            $appointment,
            'appointment_technician',
            $request->validated('observation'),
            ['technician'],
            $request->user()?->id,
        );

        return TechnicianAppoimentResource::make($appointment)->response();
    }
}
