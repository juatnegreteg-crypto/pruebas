<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('perPage', $request->query('per_page', 50));
        $perPage = max(1, min($perPage, 100));

        $taxes = Tax::query()
            ->orderBy('name')
            ->paginate($perPage);

        return TaxResource::collection($taxes);
    }

    public function store(StoreTaxRequest $request): JsonResponse
    {
        $tax = Tax::query()->create($request->validated());

        return TaxResource::make($tax)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTaxRequest $request, Tax $tax): JsonResponse
    {
        $tax->update($request->validated());

        return TaxResource::make($tax)->response();
    }

    public function destroy(Tax $tax): JsonResponse
    {
        $tax->delete();

        return response()->json([
            'message' => 'Impuesto eliminado correctamente.',
        ]);
    }
}
