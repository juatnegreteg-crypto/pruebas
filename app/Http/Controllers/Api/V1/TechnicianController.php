<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTechnicianBlockRequest;
use App\Http\Requests\StoreTechnicianRequest;
use App\Http\Resources\TechnicianResource;
use App\Models\Technician;
use App\Models\TechnicianBlock;
use App\Services\TechnicianBlockService;
use App\Services\TechnicianPartySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TechnicianController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $technicians = Technician::query()
            ->when($request->query('search'), fn ($q, $term) => $q->byNameOrEmail($term))
            ->with(['party.emails', 'party.phones', 'party.addresses'])
            ->withCount('availabilities')
            ->latest()
            ->paginate($request->query('per_page', 15))
            ->through(function (Technician $tech) {
                $tech->has_availability = $tech->availabilities_count > 0;

                return $tech;
            });

        return TechnicianResource::collection($technicians);
    }

    public function store(StoreTechnicianRequest $request, TechnicianPartySyncService $technicianPartySyncService): JsonResponse
    {
        $technician = $technicianPartySyncService->create($request->validated());

        return response()->json([
            'message' => 'Técnico creado exitosamente',
            'data' => TechnicianResource::make($technician),
        ], 201);
    }

    public function update(
        StoreTechnicianRequest $request,
        Technician $technician,
        TechnicianPartySyncService $technicianPartySyncService,
    ): JsonResponse {
        $technician = $technicianPartySyncService->update($technician, $request->validated());

        return response()->json([
            'message' => 'Técnico actualizado exitosamente',
            'data' => TechnicianResource::make($technician),
        ]);
    }

    public function blocks(Request $request, Technician $technician, TechnicianBlockService $service): JsonResponse
    {
        $from = $request->query('from') ? \Carbon\Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? \Carbon\Carbon::parse($request->query('to')) : null;

        return response()->json($service->getBlocks($technician, $from, $to));
    }

    public function storeBlock(StoreTechnicianBlockRequest $request, Technician $technician, TechnicianBlockService $service): JsonResponse
    {
        $block = $service->createBlock($technician, $request->validated());

        return response()->json([
            'message' => 'Bloqueo creado exitosamente',
            'data' => $block,
        ], 201);
    }

    public function destroyBlock(Technician $technician, TechnicianBlock $block, TechnicianBlockService $service): JsonResponse
    {
        abort_if($block->technician_id !== $technician->id, 404);

        $service->deleteBlock($block);

        return response()->json([
            'message' => 'Bloqueo eliminado exitosamente',
        ]);
    }
}
