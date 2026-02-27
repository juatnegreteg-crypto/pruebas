<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentType;
use App\Enums\PartyAddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\ListCustomersRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(ListCustomersRequest $request): Response
    {
        $perPage = (int) ($request->validated('perPage') ?? $request->validated('per_page') ?? 15);
        $search = $request->searchTerm();
        $sort = $request->validated('sort');
        $direction = $request->validated('direction');

        $customersPaginated = Customer::query()
            ->with(['party.person', 'party.organization', 'party.emails', 'party.phones', 'party.addresses'])
            ->thatAreMatchingSearchTerm($search)
            ->orderedBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('customers/Index', [
            'customers' => CustomerResource::collection($customersPaginated),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/Create', [
            'documentTypes' => DocumentType::optionsForSelect(),
            'addressTypes' => PartyAddressType::optionsForSelect(),
            'defaultCountry' => 'Colombia',
        ]);
    }

    public function store(StoreCustomerRequest $request, CustomerService $customerService): RedirectResponse
    {
        $customerService->createCustomer($request->validated(), true, $request->user()?->id);

        return redirect()->route('customers.index');
    }

    public function edit(Customer $customer): Response
    {
        $customer->loadMissing(['party.person', 'party.organization', 'party.emails', 'party.phones', 'party.addresses']);
        $party = $customer->party;
        $primaryEmail = $party?->emails->firstWhere('is_primary', true);
        $primaryPhone = $party?->phones->firstWhere('is_primary', true);
        $displayName = $party?->type === 'organization'
            ? ($party->organization?->legal_name ?? $party->display_name)
            : ($party?->person?->full_name ?? $party?->display_name);
        $addresses = $party?->addresses?->map(function ($address): array {
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

        $observation = $customer->latestObservation('general', ['internal']);
        $observations = $observation
            ? [[
                'body' => $observation->body,
                'context' => 'general',
                'audienceTags' => $observation->audience_tags ?? [],
                'createdAt' => $observation->created_at?->toIso8601String(),
                'createdBy' => $observation->created_by,
            ]]
            : [];

        return Inertia::render('customers/Edit', [
            'customer' => [
                'id' => $customer->id,
                'fullName' => $displayName ?? $customer->full_name,
                'email' => $primaryEmail?->email ?? $customer->email,
                'documentType' => $customer->document_type?->value,
                'documentNumber' => $customer->document_number,
                'phoneNumber' => $primaryPhone?->phone_number ?? $customer->phone_number,
                'observations' => $observations,
                'addresses' => $addresses ?? [],
            ],
            'documentTypes' => DocumentType::optionsForSelect(),
            'addressTypes' => PartyAddressType::optionsForSelect(),
            'defaultCountry' => 'Colombia',
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerService $customerService): RedirectResponse
    {
        $customerService->updateCustomer($customer, $request->validated(), true, $request->user()?->id);

        return redirect()->route('customers.index');
    }

    public function destroy(Customer $customer, CustomerService $customerService): RedirectResponse
    {
        $customerService->deleteCustomer($customer);

        return redirect()->route('customers.index');
    }
}
