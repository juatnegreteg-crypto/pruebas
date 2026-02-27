<?php

namespace App\Http\Controllers\Web;

use App\Enums\UnitOfMeasure;
use App\Http\Controllers\Controller;
use App\Http\Resources\BundleResource;
use App\Http\Resources\TaxResource;
use App\Models\Bundle;
use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BundleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $bundles = Bundle::query()
            ->with('catalogItem.taxRates.tax')
            ->withCount('bundleables')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('bundles/Index', [
            'bundles' => BundleResource::collection($bundles)->response()->getData(true),
            'taxes' => TaxResource::collection(Tax::query()->orderBy('name')->get())->response()->getData(true)['data'],
            'unitOptions' => UnitOfMeasure::optionsForSelect(),
            'defaultCurrency' => 'COP',
        ]);
    }

    public function show(Bundle $bundle)
    {
        $bundle->load('bundleables.bundleable', 'catalogItem.taxRates.tax')
            ->loadCount('bundleables');

        return Inertia::render('bundles/Show', [
            'bundle' => BundleResource::make($bundle)->response()->getData(true),
        ]);
    }
}
