<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $taxes = Tax::query()
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('taxes/Index', [
            'taxes' => TaxResource::collection($taxes)->response()->getData(true),
        ]);
    }
}
