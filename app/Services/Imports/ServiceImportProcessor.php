<?php

namespace App\Services\Imports;

use App\Models\Service;
use Illuminate\Validation\ValidationException;

/**
 * Processes a single service row during import (upsert logic).
 *
 * Responsibilities:
 * - Normalize row data (via ServiceImportService)
 * - Find existing service (by id)
 * - Validate row using import rules
 * - Persist (create or update) directly via Eloquent
 * - Return processing result
 *
 * Upsert strategy:
 * - If id is present and service exists -> update
 * - Otherwise -> create
 *
 * Error handling:
 * - ValidationException: Caught and returned as failed result (row marked as failed, doesn't stop chunk)
 * - Other exceptions: Caught and returned as failed result
 * - This enables best-effort processing: valid rows in chunk persist even if some rows fail validation
 * - Note: Chunk-level transaction is handled by ServicesUpsertImport
 *
 * @codeCoverageIgnore
 */
class ServiceImportProcessor
{
    public function __construct(
        private ServiceImportService $importService
    ) {}

    /**
     * Process a single row from the import file.
     *
     * @param  array  $row  Raw row data from Excel
     * @param  Service|null  $existingService  Optional pre-loaded existing service (for batch processing)
     */
    public function processRow(array $row, ?Service $existingService = null): ServiceImportRowResult
    {
        try {
            if ($existingService === null) {
                $existingService = $this->importService->findExistingService($row);
            }

            $validatedRow = $this->importService->validateRow($row, $existingService);

            if ($existingService) {
                $updated = $existingService->update($validatedRow);

                if (! $updated) {
                    return ServiceImportRowResult::failed([
                        'import' => ['Failed to update service'],
                    ]);
                }

                return ServiceImportRowResult::updated($existingService);
            }

            $service = Service::query()->create($validatedRow);

            if (! $service || ! $service->exists) {
                return ServiceImportRowResult::failed([
                    'import' => ['Failed to create service'],
                ]);
            }

            return ServiceImportRowResult::created($service);
        } catch (ValidationException $exception) {
            return ServiceImportRowResult::failed($exception->errors());
        } catch (\Throwable $throwable) {
            return ServiceImportRowResult::failed([
                'import' => [$throwable->getMessage()],
            ]);
        }
    }
}
