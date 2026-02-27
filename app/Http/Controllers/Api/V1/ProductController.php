<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogItemTaxSyncService;
use App\Services\Exports\ProductExportService;
use App\Services\ObservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductExportService $service,
    ) {}

    public function index(Request $request)
    {
        if ($request->query('format') === 'xlsx') {
            return $this->service->export(
                $request->only([
                    'search',
                    'is_active',
                    'price_from',
                    'price_to',
                ]),
                $request->input('locale', app()->getLocale())
            );
        }

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = max(1, min($perPage, 100));

        $products = Product::query()
            ->with('catalogItem.taxRates.tax')
            ->latest()
            ->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function store(
        StoreProductRequest $request,
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

        $product = Product::query()->create($data);
        $product->load('catalogItem');
        $taxSyncService->sync($product->catalogItem, $taxes);
        if ($hasObservation) {
            $observationService->syncSingleObservation(
                $product->catalogItem,
                'general',
                $observation,
                ['internal'],
                $request->user()?->id,
            );
        }

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        CatalogItemTaxSyncService $taxSyncService,
        ObservationService $observationService
    ): JsonResponse {
        $data = $request->validated();
        $taxes = $data['taxes'] ?? null;
        $observation = $data['observation'] ?? null;
        unset($data['taxes'], $data['observation']);
        $data['currency'] = $data['currency'] ?? $product->currency;
        $data['unit'] = $data['unit'] ?? ($product->unit?->value ?? $product->unit);
        $data['is_active'] = $data['isActive'] ?? $product->is_active;
        unset($data['isActive']);

        $product->update($data);
        $product->load('catalogItem');
        $taxSyncService->sync($product->catalogItem, $taxes);
        $observationService->syncSingleObservation(
            $product->catalogItem,
            'general',
            $observation,
            ['internal'],
            $request->user()?->id,
        );

        return ProductResource::make($product)->response();
    }

    public function queueExport(Request $request): JsonResponse
    {
        $export = $this->service->queueExport(
            $request->only([
                'search',
                'is_active',
                'price_from',
                'price_to',
            ]),
            $request->input('locale', app()->getLocale())
        );

        return response()->json([
            ...$export,
            'status_url' => route('api.v1.product-exports.show', ['exportId' => $export['export_id']]),
            'download_url' => route('api.v1.product-exports.show', ['exportId' => $export['export_id'], 'format' => 'xlsx']),
        ], Response::HTTP_ACCEPTED);
    }

    public function exportStatus(Request $request, string $exportId): StreamedResponse|JsonResponse
    {
        if ($request->query('format') === 'xlsx') {
            $download = $this->service->downloadExport($exportId);

            if ($download instanceof StreamedResponse) {
                return $download;
            }

            return response()->json([
                'message' => 'Export no disponible para descarga.',
            ], Response::HTTP_NOT_FOUND);
        }

        $status = $this->service->getStatus($exportId);

        if (! is_array($status)) {
            return response()->json([
                'message' => 'Export no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'export_id' => $exportId,
            'status' => $status['status'],
            'error' => $status['error'],
            'download_url' => $status['status'] === 'completed'
                ? route('api.v1.product-exports.show', ['exportId' => $exportId, 'format' => 'xlsx'])
                : null,
        ]);
    }
}
