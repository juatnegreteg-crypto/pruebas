<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\CatalogItemTaxSyncService;
use App\Services\ObservationService;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index()
    {
        $perPage = (int) request()->query('perPage', request()->query('per_page', 15));
        $perPage = max(1, min($perPage, 100));

        $services = Service::query()
            ->with('catalogItem.taxRates.tax')
            ->latest()
            ->paginate($perPage);

        return ServiceResource::collection($services);
    }

    public function store(
        StoreServiceRequest $request,
        CatalogItemTaxSyncService $taxSyncService,
        ObservationService $observationService
    ): JsonResponse {
        $data = $request->validated();
        $taxes = $data['taxes'] ?? null;
        $hasObservation = array_key_exists('observation', $data);
        $observation = $data['observation'] ?? null;
        unset($data['taxes'], $data['observation']);
        $data['is_active'] = $data['isActive'] ?? true;
        unset($data['isActive']);

        $service = Service::query()->create($data);
        $service->load('catalogItem');
        $taxSyncService->sync($service->catalogItem, $taxes);
        if ($hasObservation) {
            $observationService->syncSingleObservation(
                $service->catalogItem,
                'general',
                $observation,
                ['internal'],
                $request->user()?->id,
            );
        }

        return ServiceResource::make($service)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateServiceRequest $request,
        Service $service,
        CatalogItemTaxSyncService $taxSyncService,
        ObservationService $observationService
    ): JsonResponse {
        $data = $request->validated();
        $taxes = $data['taxes'] ?? null;
        $observation = $data['observation'] ?? null;
        unset($data['taxes'], $data['observation']);
        $data['currency'] = $data['currency'] ?? $service->currency;
        $data['unit'] = $data['unit'] ?? ($service->unit?->value ?? $service->unit);
        $data['is_active'] = $data['isActive'] ?? $service->is_active;
        unset($data['isActive']);

        $service->update($data);
        $service->load('catalogItem');
        $taxSyncService->sync($service->catalogItem, $taxes);
        $observationService->syncSingleObservation(
            $service->catalogItem,
            'general',
            $observation,
            ['internal'],
            $request->user()?->id,
        );

        return ServiceResource::make($service)->response();
    }
}
