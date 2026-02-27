<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('quotes/Index', [
            'quotes' => QuoteResource::collection($quotes)->response()->getData(true),
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    public function show(Quote $quote)
    {
        $quote->load(['vehicle.customer', 'items.itemable']);

        return Inertia::render('quotes/Show', [
            'quote' => QuoteResource::make($quote)->response()->getData(true)['data'],
        ]);
    }

    /**
     * Genera y descarga el PDF de la cotizacion.
     */
    public function pdf(Quote $quote)
    {
        $quote->load(['vehicle.customer', 'items.itemable']);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
        ]);

        return $pdf->download("cotizacion-{$quote->id}.pdf");
    }

    /**
     * Muestra el PDF de la cotizacion en el navegador.
     */
    public function viewPdf(Quote $quote)
    {
        $quote->load(['vehicle.customer', 'items.itemable']);

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
        ]);

        return $pdf->stream("cotizacion-{$quote->id}.pdf");
    }
}
