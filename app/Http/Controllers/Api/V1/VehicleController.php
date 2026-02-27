<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\ObservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = max(1, min($perPage, 100));

        $search = $request->string('q')->toString();
        $search = $search !== '' ? $search : $request->string('search')->toString(); // legacy fallback

        $customerId = null;
        $filterCustomerId = $request->input('filter.customerId');
        if (is_numeric($filterCustomerId)) {
            $customerId = (int) $filterCustomerId;
        } else {
            $customerId = $request->integer('customer_id');
        }

        $vehicles = Vehicle::query()
            ->with('customer')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->thatAreMatchingSearchTerm($search)
            ->latest()
            ->paginate($perPage);

        return VehicleResource::collection($vehicles);
    }

    public function store(StoreVehicleRequest $request, ObservationService $observationService): JsonResponse
    {
        $data = $request->validated();
        $observation = $data['observation'] ?? null;
        unset($data['observation']);
        $data['is_active'] = $data['is_active'] ?? true;

        $vehicle = Vehicle::query()->create($data);
        $observationService->syncSingleObservation(
            $vehicle,
            'general',
            $observation,
            ['internal'],
            $request->user()?->id,
        );

        return VehicleResource::make($vehicle)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
        ObservationService $observationService
    ): JsonResponse {
        $data = $request->validated();
        $hasObservation = array_key_exists('observation', $data);
        $observation = $data['observation'] ?? null;
        unset($data['observation']);
        $data['is_active'] = $data['is_active'] ?? $vehicle->is_active;

        $vehicle->update($data);
        if ($hasObservation) {
            $observationService->syncSingleObservation(
                $vehicle,
                'general',
                $observation,
                ['internal'],
                $request->user()?->id,
            );
        }

        return VehicleResource::make($vehicle)->response();
    }
}
