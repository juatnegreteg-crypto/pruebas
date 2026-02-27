<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));
        $search = $request->string('q')->toString();

        $vehicles = Vehicle::query()
            ->with('customer')
            ->thatAreMatchingSearchTerm($search)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('vehicles/Index', [
            'vehicles' => VehicleResource::collection($vehicles)->response()->getData(true),
        ]);
    }
}
