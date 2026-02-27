<?php

namespace App\Services\Imports;

use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Processes a single customer row during import (upsert logic).
 *
 * Architecture:
 * - This processor is the single entry point for row processing
 * - Import adapter (CustomersUpsertImport) delegates all business logic here
 * - This class orchestrates normalization, validation, and persistence
 *
 * Responsibilities:
 * - Normalize row data (via CustomerNormalizer)
 * - Validate row using appropriate FormRequest rules (via CustomerImportService)
 * - Persist (create or update) via CustomerService
 * - Return structured processing result
 *
 * Performance optimization:
 * - Existing customer MUST be provided by caller (pre-loaded via batch lookup)
 * - This enables 2 queries per chunk instead of N*2 queries
 *
 * Error handling:
 * - ValidationException: Caught and returned as failed result (row marked as failed, doesn't stop import)
 * - Other exceptions: Caught and returned as failed result (prevents transaction rollback)
 * - This enables best-effort processing: valid rows persist even if some rows fail validation
 */
class CustomerImportProcessor
{
    public function __construct(
        private CustomerImportService $importService,
        private CustomerService $customerService
    ) {}

    /**
     * Process a single row from the import file.
     *
     * @param  array  $row  Raw row data from Excel
     * @param  Customer|null  $existingCustomer  Pre-loaded existing customer (null = new customer)
     */
    public function processRow(array $row, ?Customer $existingCustomer): CustomerImportRowResult
    {
        try {

            $validatedRow = $this->importService->validateRow($row, $existingCustomer);

            if ($existingCustomer) {
                $customer = $this->customerService->updateCustomer($existingCustomer, $validatedRow, false);

                // Verify persistence succeeded before returning success
                if (! $customer || ! $customer->exists) {
                    return CustomerImportRowResult::failed([
                        'import' => ['Failed to update customer'],
                    ]);
                }

                return CustomerImportRowResult::updated();
            }

            $customer = $this->customerService->createCustomer($validatedRow, false);

            // Verify persistence succeeded before returning success
            if (! $customer || ! $customer->exists) {
                return CustomerImportRowResult::failed([
                    'import' => ['Failed to create customer'],
                ]);
            }

            return CustomerImportRowResult::created();
        } catch (ValidationException $exception) {
            return CustomerImportRowResult::failed($exception->errors());
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                return CustomerImportRowResult::failed([
                    'import' => ['Registro duplicado detectado durante la importación.'],
                ]);
            }

            return CustomerImportRowResult::failed([
                'import' => [$exception->getMessage()],
            ]);
        } catch (\Throwable $throwable) {
            return CustomerImportRowResult::failed([
                'import' => [$throwable->getMessage()],
            ]);
        }
    }
}
