<?php

namespace App\Http\Controllers\Web;

use App\Enums\UnitOfMeasure;
use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ImportProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TaxResource;
use App\Imports\ProductsUpsertImport;
use App\Models\Product;
use App\Models\Tax;
use App\Services\CatalogItemTaxSyncService;
use App\Services\Imports\ProductImportProcessor;
use App\Services\Imports\ProductImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $products = Product::query()
            ->with('catalogItem.taxRates.tax')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('products/Index', [
            'products' => ProductResource::collection($products)->response()->getData(true),
            'taxes' => TaxResource::collection(Tax::query()->orderBy('name')->get())->response()->getData(true)['data'],
            'unitOptions' => UnitOfMeasure::optionsForSelect(),
            'defaultCurrency' => 'COP',
        ]);
    }

    public function create()
    {
        return Inertia::render('products/Create');
    }

    public function store(StoreProductRequest $request, CatalogItemTaxSyncService $taxSyncService)
    {
        $data = $request->validated();
        $taxes = $data['taxes'] ?? null;
        unset($data['taxes']);
        $data['is_active'] = $data['isActive'] ?? true;
        unset($data['isActive']);

        $product = Product::query()->create($data);
        $product->load('catalogItem');
        $taxSyncService->sync($product->catalogItem, $taxes);

        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        return Inertia::render('products/Edit', [
            'product' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, CatalogItemTaxSyncService $taxSyncService)
    {
        $data = $request->validated();
        $taxes = $data['taxes'] ?? null;
        unset($data['taxes']);
        $data['currency'] = $data['currency'] ?? $product->currency;
        $data['unit'] = $data['unit'] ?? ($product->unit?->value ?? $product->unit);
        $data['is_active'] = $data['isActive'] ?? $product->is_active;
        unset($data['isActive']);

        $product->update($data);
        $product->load('catalogItem');
        $taxSyncService->sync($product->catalogItem, $taxes);

        return redirect()->route('products.index');
    }

    public function import(
        ImportProductsRequest $request,
        ProductImportProcessor $processor,
        ProductImportService $importService
    ): JsonResponse {
        $file = $request->file('file');

        try {
            $importService->validateFile($file);

            $productsImport = new ProductsUpsertImport($processor, $importService);

            $result = DB::transaction(function () use ($productsImport, $file) {
                $productsImport->import($file);

                return $productsImport->result();
            });

            return response()->json([
                'message' => 'Importación procesada exitosamente',
                'result' => $result->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Error de validación del archivo',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'La importación falló y no se guardaron cambios.',
                'error' => 'Ocurrió un error inesperado durante la importación.',
            ], 422);
        }
    }

    public function template(ProductImportService $importService): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $internal = $importService->requiredHeaders();

        $map = [
            'sku' => 'SKU',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'cost' => 'Costo',
            'price' => 'Precio',
            'currency' => 'Moneda',
            'unit' => 'Unidad',
            'is_active' => 'Estado',
            'stock' => 'Stock',
        ];

        $headers = array_map(fn ($h) => $map[$h] ?? $h, $internal);

        return Excel::download(
            new ProductTemplateExport($headers),
            'plantilla-productos.xlsx'
        );
    }
}
