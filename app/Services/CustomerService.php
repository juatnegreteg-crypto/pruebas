<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    private readonly CustomerPartySyncService $customerPartySyncService;

    private readonly ObservationService $observationService;

    public function __construct(
        ?CustomerPartySyncService $customerPartySyncService = null,
        ?ObservationService $observationService = null
    ) {
        $this->customerPartySyncService = $customerPartySyncService ?? app(CustomerPartySyncService::class);
        $this->observationService = $observationService ?? app(ObservationService::class);
    }

    public function createCustomer(array $data, bool $useTransaction = true, ?int $userId = null): Customer
    {
        $customer = $this->customerPartySyncService->create($data, $useTransaction);
        if (array_key_exists('observation', $data)) {
            $this->observationService->syncSingleObservation(
                $customer,
                'general',
                $data['observation'] ?? null,
                ['internal'],
                $userId,
            );
        }

        return $customer;
    }

    public function updateCustomer(Customer $customer, array $data, bool $useTransaction = true, ?int $userId = null): Customer
    {
        $customer = $this->customerPartySyncService->update($customer, $data, $useTransaction);
        if (array_key_exists('observation', $data)) {
            $this->observationService->syncSingleObservation(
                $customer,
                'general',
                $data['observation'] ?? null,
                ['internal'],
                $userId,
            );
        }

        return $customer;
    }

    public function deleteCustomer(Customer $customer): void
    {
        $customer->delete();
    }
}
