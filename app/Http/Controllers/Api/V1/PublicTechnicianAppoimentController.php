<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetAppointmentAvailabilityRequest;
use App\Http\Requests\StoreTechnicianAppointmentRequest;
use App\Http\Resources\AppointmentAvailabilityDayResource;
use App\Http\Resources\TechnicianAppoimentResource;
use App\Models\TechnicianAppoiment;
use App\Models\TechnicianBlock;
use App\Services\ObservationService;
use App\Services\ScheduleService;
use App\Services\Technicians\AgendaClient;
use App\Services\Technicians\Contracts\AgendaSlotAvailabilityData;
use App\Services\Technicians\Contracts\AgendaSlotWindowData;
use App\Services\Technicians\Contracts\TechnicianReference;
use App\Services\Technicians\CreateTechnicianAppoimentAction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicTechnicianAppoimentController extends Controller
{
    public function __construct(
        private readonly CreateTechnicianAppoimentAction $createAppointmentAction,
    ) {}

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

        $excludingAppointmentReference = null;

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
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        $payloadFingerprint = hash('sha256', (string) json_encode($request->validated()));

        if ($idempotencyKey === '') {
            $appointment = $this->createAppointmentAction->execute(
                $request->slotWindow(),
                $request->technicianReference(),
            );
            $observationService->syncSingleObservation(
                $appointment,
                'appointment_request',
                $request->validated('observation'),
                ['customer'],
                null,
            );

            return TechnicianAppoimentResource::make($appointment)
                ->response()
                ->setStatusCode(201);
        }

        $cacheKey = "public-appointments:idempotency:{$idempotencyKey}";
        $lockKey = "{$cacheKey}:lock";

        try {
            $result = Cache::lock($lockKey, 5)->block(5, function () use ($cacheKey, $payloadFingerprint, $request, $observationService): array {
                $cached = Cache::get($cacheKey);

                if (is_array($cached)) {
                    $cachedFingerprint = data_get($cached, 'fingerprint');

                    if ($cachedFingerprint !== $payloadFingerprint) {
                        return [
                            'type' => 'payload_mismatch',
                        ];
                    }

                    $cachedAppointmentId = data_get($cached, 'appointmentId');
                    $appointment = is_int($cachedAppointmentId)
                        ? TechnicianAppoiment::query()->find($cachedAppointmentId)
                        : null;

                    if ($appointment !== null) {
                        return [
                            'type' => 'replayed',
                            'appointment' => $appointment,
                        ];
                    }
                }

                $appointment = $this->createAppointmentAction->execute(
                    $request->slotWindow(),
                    $request->technicianReference(),
                );
                $observationService->syncSingleObservation(
                    $appointment,
                    'appointment_request',
                    $request->validated('observation'),
                    ['customer'],
                    null,
                );

                Cache::put($cacheKey, [
                    'fingerprint' => $payloadFingerprint,
                    'appointmentId' => $appointment->id,
                ], now()->addMinutes(30));

                return [
                    'type' => 'created',
                    'appointment' => $appointment,
                ];
            });
        } catch (LockTimeoutException) {
            return response()->json([
                'code' => 'IDEMPOTENCY_LOCK_TIMEOUT',
                'message' => 'No fue posible procesar la reserva. Intente nuevamente.',
                'errors' => [
                    'idempotencyKey' => [
                        'No fue posible procesar la reserva. Intente nuevamente.',
                    ],
                ],
            ], 409);
        }

        if (($result['type'] ?? null) === 'payload_mismatch') {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH',
                'message' => 'La clave de idempotencia ya fue usada con otra solicitud.',
                'errors' => [
                    'idempotencyKey' => [
                        'La clave de idempotencia ya fue usada con otra solicitud.',
                    ],
                ],
            ], 409);
        }

        /** @var TechnicianAppoiment $appointment */
        $appointment = $result['appointment'];

        return TechnicianAppoimentResource::make($appointment)
            ->response()
            ->setStatusCode(($result['type'] ?? 'created') === 'replayed' ? 200 : 201);
    }
}
