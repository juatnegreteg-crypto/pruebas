<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetScheduleAvailabilityRequest;
use App\Http\Requests\GetScheduleOverridesRequest;
use App\Http\Requests\StoreScheduleOverrideRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\ScheduleOverride;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->scheduleService->getWeeklySchedule(),
            'is_configured' => $this->scheduleService->isConfigured(),
        ]);
    }

    public function update(StoreScheduleRequest $request): JsonResponse
    {
        $this->scheduleService->saveWeeklySchedule($request->validated('days'));

        return response()->json([
            'message' => 'Configuración semanal guardada exitosamente',
            'data' => $this->scheduleService->getWeeklySchedule(),
        ]);
    }

    public function overrides(GetScheduleOverridesRequest $request): JsonResponse
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()));

        return response()->json([
            'data' => $this->scheduleService->getOverrides($from, $to),
        ]);
    }

    public function storeOverride(StoreScheduleOverrideRequest $request): JsonResponse
    {
        $override = $this->scheduleService->addOverride($request->validated());

        return response()->json([
            'message' => 'Excepción creada exitosamente',
            'data' => $override,
        ], 201);
    }

    public function destroyOverride(ScheduleOverride $override): JsonResponse
    {
        $this->scheduleService->removeOverride($override);

        return response()->json([
            'message' => 'Excepción eliminada exitosamente',
        ]);
    }

    public function availability(GetScheduleAvailabilityRequest $request): JsonResponse
    {
        $from = Carbon::parse($request->query('from', now()));
        $to = Carbon::parse($request->query('to', now()->addDays(7)));

        return response()->json([
            'data' => $this->scheduleService->getAvailableSlots($from, $to),
        ]);
    }
}
