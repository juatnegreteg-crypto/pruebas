<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianOptionResource;
use App\Models\Technician;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function index(): Response
    {
        $technicians = Technician::query()
            ->thatAreActive()
            ->with('party')
            ->orderBy('id')
            ->get(['id', 'party_id']);

        return Inertia::render('agenda/Index', [
            'technicians' => TechnicianOptionResource::collection($technicians)->resolve(),
        ]);
    }
}
