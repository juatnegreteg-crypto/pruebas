<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBundleRequest;
use App\Http\Requests\UpdateBundleRequest;
use App\Http\Resources\BundleResource;
use App\Models\Bundle;
use App\Services\CatalogItemTaxSyncService;
use App\Services\ObservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BundleController extends Controller
{
    public function index()
    {
        $perPage = (int) request()->query('perPage', request()->query('per_page', 15));
        $perPage = max(1, min($perPage, 100));

        $bundles = Bundle::query()
            ->with('catalogItem.taxRates.tax')
            ->withCount('bundleables')
            ->latest()
            ->paginate($perPage);

        return BundleResource::collection($bundles);
    }

    public function store(
        StoreBundleRequest $request,
        CatalogItemTaxSyncService $taxSyncService,
        ObservationService $observationService
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        $taxes = $validated['taxes'] ?? null;
        $observation = $validated['observation'] ?? null;
        unset($validated['items'], $validated['taxes'], $validated['observation']);

        $validated['is_active'] = $validated['isActive'] ?? true;
        unset($validated['isActive']);

        $bundle = DB::transaction(function () use ($validated, $items): Bundle {
            $bundle = Bundle::query()->create($validated);
            $this->syncBundleItems($bundle, $items);

            return $bundle;
        });

        $bundle->load('catalogItem')
            ->loadCount('bundleables');
        $taxSyncService->sync($bundle->catalogItem, $taxes);
        $observationService->syncSingleObservation(
            $bundle->catalogItem,
            'general',
            $observation,
            ['internal'],
            $request->user()?->id,
        );

        return BundleResource::make($bundle)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Bundle $bundle)
    {
        $bundle->load('bundleables.bundleable', 'catalogItem.taxRates.tax')
            ->loadCount('bundleables');

        return BundleResource::make($bundle);
    }

    public function update(
        UpdateBundleRequest $request,
        Bundle $bundle,
        CatalogItemTaxSyncService $taxSyncService,
        ObservationService $observationService
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        $taxes = $validated['taxes'] ?? null;
        $hasObservation = array_key_exists('observation', $validated);
        $observation = $validated['observation'] ?? null;
        unset($validated['items'], $validated['taxes'], $validated['observation']);

        $validated['currency'] = $validated['currency'] ?? $bundle->currency;
        $validated['unit'] = $validated['unit'] ?? ($bundle->unit?->value ?? $bundle->unit);
        $validated['is_active'] = $validated['isActive'] ?? $bundle->is_active;
        unset($validated['isActive']);

        DB::transaction(function () use ($bundle, $validated, $items): void {
            $bundle->update($validated);

            if ($items !== null) {
                $this->syncBundleItems($bundle, $items);
            }
        });

        $bundle->load('catalogItem')
            ->loadCount('bundleables');
        $taxSyncService->sync($bundle->catalogItem, $taxes);
        if ($hasObservation) {
            $observationService->syncSingleObservation(
                $bundle->catalogItem,
                'general',
                $observation,
                ['internal'],
                $request->user()?->id,
            );
        }

        return BundleResource::make($bundle)->response();
    }

    /**
     * @param  array<int, array{type: string, id: int, quantity: int}>  $items
     */
    private function syncBundleItems(Bundle $bundle, array $items): void
    {
        $products = [];
        $services = [];
        $bundles = [];
        $itemsCount = 0;

        foreach ($items as $item) {
            $payload = ['quantity' => $item['quantity']];
            $itemsCount += $item['quantity'];

            if ($item['type'] === 'product') {
                $products[$item['id']] = $payload;

                continue;
            }

            if ($item['type'] === 'service') {
                $services[$item['id']] = $payload;

                continue;
            }

            if ($item['type'] === 'bundle') {
                $bundles[$item['id']] = $payload;
            }
        }

        $bundle->products()->sync($products);
        $bundle->services()->sync($services);
        $bundle->bundles()->sync($bundles);
        $bundle->update(['items_count' => $itemsCount]);
    }
}
