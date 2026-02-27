<?php

namespace App\Http\Controllers\Web;

use App\Enums\UnitOfMeasure;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TaxResource;
use App\Models\Service;
use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $services = Service::query()
            ->with('catalogItem.taxRates.tax')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('services/Index', [
            'services' => ServiceResource::collection($services)->response()->getData(true),
            'taxes' => TaxResource::collection(Tax::query()->orderBy('name')->get())->response()->getData(true)['data'],
            'unitOptions' => UnitOfMeasure::optionsForSelect(),
            'defaultCurrency' => 'COP',
        ]);
    }
}
