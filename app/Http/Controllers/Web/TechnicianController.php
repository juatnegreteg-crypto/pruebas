<?php

namespace App\Http\Controllers\Web;

use App\Enums\DayOfWeek;
use App\Enums\PartyAddressType;
use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Services\ScheduleService;
use App\Services\TechnicianAvailabilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function __construct(
        private readonly TechnicianAvailabilityService $availabilityService,
        private readonly ScheduleService $scheduleService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $technicians = Technician::query()
            ->when($search, fn ($q, $term) => $q->byNameOrEmail($term))
            ->with(['party.addresses'])
            ->withCount('availabilities')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Technician $tech): array {
                $addresses = $tech->party?->addresses?->map(function ($address): array {
                    return [
                        'id' => $address->id,
                        'type' => $address->type,
                        'isPrimary' => (bool) $address->is_primary,
                        'street' => $address->street,
                        'complement' => $address->complement,
                        'neighborhood' => $address->neighborhood,
                        'city' => $address->city,
                        'state' => $address->state,
                        'postalCode' => $address->postal_code,
                        'country' => $address->country,
                        'reference' => $address->reference,
                    ];
                })->values();

                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'email' => $tech->email,
                    'phone' => $tech->phone,
                    'isActive' => (bool) $tech->is_active,
                    'hasAvailability' => $tech->availabilities_count > 0,
                    'addresses' => $addresses ?? [],
                ];
            });

        return Inertia::render('technicians/Index', [
            'technicians' => $technicians,
            'filters' => [
                'search' => $search,
            ],
            'addressTypes' => PartyAddressType::optionsForSelect(),
            'defaultCountry' => 'Colombia',
        ]);
    }

    public function show(Technician $technician): Response
    {
        $technician->loadMissing('party');

        return Inertia::render('technicians/Show', [
            'technician' => $technician,
            'availability' => $this->availabilityService->getAvailability($technician),
            'hasAvailability' => $this->availabilityService->hasAvailability($technician),
            'cdaSchedule' => $this->scheduleService->getWeeklySchedule(),
            'dayValues' => DayOfWeek::optionsForSelect(),
        ]);
    }
}
