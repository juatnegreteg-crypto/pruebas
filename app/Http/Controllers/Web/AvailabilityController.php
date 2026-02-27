<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianOptionResource;
use App\Models\Technician;
use App\Services\ScheduleService;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(ScheduleService $scheduleService): Response
    {
        $technicians = Technician::query()
            ->thatAreActive()
            ->with('party')
            ->orderBy('id')
            ->get(['id', 'party_id']);

        return Inertia::render('availability/Index', [
            'isConfigured' => $scheduleService->isConfigured(),
            'hasTechnicians' => $technicians->isNotEmpty(),
            'technicians' => TechnicianOptionResource::collection($technicians)->resolve(),
        ]);
    }
}
