<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteItemRequest;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = max(1, min($perPage, 100));

        $search = $request->string('q')->toString();

        $quotes = Quote::query()
            ->with(['vehicle.customer'])
            ->withCount('items')
            ->when($search, function ($query) use ($search) {
                $pattern = "%{$search}%";
                $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                $query->where(function ($subQuery) use ($search, $pattern, $operator) {
                    if (is_numeric($search)) {
                        $subQuery->orWhere('id', (int) $search);
                    }

                    $subQuery->orWhereHas('vehicle', function ($vehicleQuery) use ($pattern, $operator) {
                        $vehicleQuery->where('plate', $operator, $pattern)
                            ->orWhere('vin', $operator, $pattern)
                            ->orWhere('make', $operator, $pattern)
                            ->orWhere('model', $operator, $pattern);
                    })->orWhereHas('vehicle.customer', function ($customerQuery) use ($pattern, $operator) {
                        $customerQuery->where('full_name', $operator, $pattern)
                            ->orWhere('document_number', $operator, $pattern);
                    });
                });
            })
            ->latest()
            ->paginate($perPage);

        return QuoteResource::collection($quotes);
    }

    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $quote = Quote::query()->create([
            'vehicle_id' => $data['vehicle_id'],
            'status' => QuoteStatus::DRAFT,
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
        ]);

        foreach ($data['items'] as $itemData) {
            $item = new QuoteItem([
                'quote_id' => $quote->id,
                'itemable_type' => $itemData['itemable_type'],
                'itemable_id' => $itemData['itemable_id'],
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'] ?? 19,
            ]);
            $item->save();
        }

        $quote->refresh()->load(['vehicle.customer', 'items']);

        return QuoteResource::make($quote)
            ->response()
            ->setStatusCode(201);
    }

    public function export(): BinaryFileResponse
    {
        $rows = Quote::query()
            ->with(['vehicle.customer'])
            ->select('id', 'vehicle_id', 'total', 'created_at')
            ->latest()
            ->get()
            ->map(function (Quote $quote): array {
                return [
                    'quote_id' => $quote->id,
                    'customer' => $quote->vehicle?->customer?->full_name ?? 'N/A',
                    'vehicle' => $this->vehicleLabel($quote),
                    'quoted_at' => $quote->created_at?->format('Y-m-d H:i:s'),
                    'total_quoted' => (string) $quote->total,
                ];
            })
            ->all();

        return Excel::download(
            new class($rows) implements FromArray, WithColumnWidths, WithHeadings
            {
                public function __construct(private readonly array $rows) {}

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'Identificador de cotizacion',
                        'Cliente',
                        'Vehiculo',
                        'Fecha de cotizacion',
                        'Total cotizado',
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 30.0,
                        'B' => 34.0,
                        'C' => 30.0,
                        'D' => 24.0,
                        'E' => 20.0,
                    ];
                }
            },
            'cotizaciones-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function exportDetailed(): BinaryFileResponse
    {
        $rows = Quote::query()
            ->with(['vehicle.customer', 'items'])
            ->select('id', 'vehicle_id', 'subtotal', 'tax_total', 'total')
            ->latest()
            ->get()
            ->flatMap(function (Quote $quote): array {
                if ($quote->items->isEmpty()) {
                    return [[
                        'quote_id' => $quote->id,
                        'customer' => $quote->vehicle?->customer?->full_name ?? 'N/A',
                        'vehicle' => $this->vehicleLabel($quote),
                        'product_quoted' => 'N/A',
                        'service_quoted' => 'N/A',
                        'quantity' => 'N/A',
                        'unit_price' => 'N/A',
                        'item_total' => 'N/A',
                        'quote_total' => (string) $quote->total,
                    ]];
                }

                return $quote->items->map(function (QuoteItem $item) use ($quote): array {
                    $isProduct = $item->itemable_type === 'App\\Models\\Product';
                    $isService = $item->itemable_type === 'App\\Models\\Service';

                    return [
                        'quote_id' => $quote->id,
                        'customer' => $quote->vehicle?->customer?->full_name ?? 'N/A',
                        'vehicle' => $this->vehicleLabel($quote),
                        'product_quoted' => $isProduct ? ($item->description ?: 'N/A') : 'N/A',
                        'service_quoted' => $isService ? ($item->description ?: 'N/A') : 'N/A',
                        'quantity' => (string) $item->quantity,
                        'unit_price' => (string) $item->unit_price,
                        'item_total' => (string) $item->total,
                        'quote_total' => (string) $quote->total,
                    ];
                })->all();
            })
            ->values()
            ->all();

        return Excel::download(
            new class($rows) implements FromArray, WithColumnWidths, WithHeadings
            {
                public function __construct(private readonly array $rows) {}

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'Identificador de cotizacion',
                        'Cliente',
                        'Vehiculo',
                        'Producto cotizado',
                        'Servicio cotizado',
                        'Cantidad',
                        'Precio unitario',
                        'Total item',
                        'Total cotizacion',
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 30.0,
                        'B' => 32.0,
                        'C' => 34.0,
                        'D' => 36.0,
                        'E' => 36.0,
                        'F' => 16.0,
                        'G' => 20.0,
                        'H' => 20.0,
                        'I' => 22.0,
                    ];
                }
            },
            'cotizaciones-detallado-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function show(Quote $quote): QuoteResource
    {
        $quote->refresh()->load(['vehicle.customer', 'items']);

        return QuoteResource::make($quote);
    }

    public function update(UpdateQuoteRequest $request, Quote $quote): JsonResponse
    {
        $data = $request->validated();

        $updatableAttributes = [];
        if (array_key_exists('vehicle_id', $data)) {
            $updatableAttributes['vehicle_id'] = $data['vehicle_id'];
        }
        if (! empty($updatableAttributes)) {
            $quote->update($updatableAttributes);
        }

        if (isset($data['items'])) {
            // Obtener IDs de items existentes para determinar cuales eliminar
            $existingItemIds = $quote->items()->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($data['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    // Actualizar item existente
                    $item = $quote->items()->find($itemData['id']);
                    if ($item) {
                        $item->fill([
                            'itemable_type' => $itemData['itemable_type'],
                            'itemable_id' => $itemData['itemable_id'],
                            'description' => $itemData['description'],
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'tax_rate' => $itemData['tax_rate'] ?? $item->tax_rate,
                        ]);
                        $item->save();
                        $updatedItemIds[] = $item->id;
                    }
                } else {
                    // Crear nuevo item
                    $item = new QuoteItem([
                        'quote_id' => $quote->id,
                        'itemable_type' => $itemData['itemable_type'],
                        'itemable_id' => $itemData['itemable_id'],
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'] ?? 19,
                    ]);
                    $item->save();
                    $updatedItemIds[] = $item->id;
                }
            }

            // Eliminar items que no fueron incluidos en la actualizacion
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (! empty($itemsToDelete)) {
                QuoteItem::whereIn('id', $itemsToDelete)->delete();
            }

        }

        $quote->refresh()->load(['vehicle.customer', 'items']);

        return QuoteResource::make($quote)->response();
    }

    public function destroy(Quote $quote): JsonResponse
    {
        $quote->items()->delete();
        $quote->delete();

        return response()->json(null, 204);
    }

    /**
     * Agrega un item a una cotizacion existente.
     */
    public function addItem(StoreQuoteItemRequest $request, Quote $quote): JsonResponse
    {
        $data = $request->validated();

        $item = new QuoteItem([
            'quote_id' => $quote->id,
            'itemable_type' => $data['itemable_type'],
            'itemable_id' => $data['itemable_id'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'tax_rate' => $data['tax_rate'] ?? 19,
        ]);
        $item->save();

        $quote->refresh()->load('items');

        return QuoteResource::make($quote)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Elimina un item de una cotizacion.
     */
    public function removeItem(Quote $quote, QuoteItem $item): JsonResponse
    {
        if ($item->quote_id !== $quote->id) {
            return response()->json(['message' => 'Item no pertenece a esta cotizacion'], 404);
        }

        $item->delete();

        return response()->json(null, 204);
    }

    /**
     * Confirma una cotizacion.
     */
    public function confirm(Quote $quote): JsonResponse
    {
        if ($quote->status === QuoteStatus::CANCELLED) {
            return response()->json(['message' => 'No se puede confirmar una cotizacion anulada'], 422);
        }

        $quote->update(['status' => QuoteStatus::CONFIRMED]);
        $quote->loadCount('items');

        return QuoteResource::make($quote)->response();
    }

    /**
     * Anula una cotizacion.
     */
    public function cancel(Quote $quote): JsonResponse
    {
        if ($quote->status === QuoteStatus::CANCELLED) {
            return response()->json(['message' => 'La cotizacion ya esta anulada'], 422);
        }

        $quote->update(['status' => QuoteStatus::CANCELLED]);
        $quote->loadCount('items');

        return QuoteResource::make($quote)->response();
    }

    private function vehicleLabel(Quote $quote): string
    {
        $vehicle = $quote->vehicle;

        if (! $vehicle) {
            return 'N/A';
        }

        $description = trim("{$vehicle->make} {$vehicle->model}");

        if ($vehicle->plate && $description !== '') {
            return "{$vehicle->plate} - {$description}";
        }

        return $vehicle->plate ?: ($description !== '' ? $description : 'N/A');
    }
}
