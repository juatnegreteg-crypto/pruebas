<?php

namespace App\Http\Controllers\Web;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Services\ScheduleService;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(ScheduleService $scheduleService): Response
    {
        return Inertia::render('schedule/Index', [
            'schedule' => $scheduleService->getWeeklySchedule()->whenEmpty(fn () => null),
            'overrides' => $scheduleService->getAllOverrides(),
            'isConfigured' => $scheduleService->isConfigured(),
            'dayValues' => DayOfWeek::optionsForSelect(),
        ]);
    }
}
