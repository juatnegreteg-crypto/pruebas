<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTechnicianAvailabilityRequest;
use App\Models\Technician;
use App\Services\TechnicianAvailabilityService;
use Illuminate\Http\JsonResponse;

class TechnicianAvailabilityController extends Controller
{
    public function __construct(
        private readonly TechnicianAvailabilityService $availabilityService,
    ) {}

    public function show(Technician $technician): JsonResponse
    {
        return response()->json([
            'data' => $this->availabilityService->getAvailability($technician),
        ]);
    }

    public function update(StoreTechnicianAvailabilityRequest $request, Technician $technician): JsonResponse
    {
        $this->availabilityService->saveAvailability($technician, $request->validated('days'));

        return response()->json([
            'message' => 'Disponibilidad guardada exitosamente',
            'data' => $this->availabilityService->getAvailability($technician),
        ]);
    }
}
