<?php

namespace App\Imports;

use App\Enums\CatalogItemType;
use App\Models\ProcessJob;
use App\Services\Imports\ServiceImportProcessor;
use App\Services\Imports\ServiceImportResult;
use App\Services\Imports\ServiceImportRowResult;
use App\Services\Imports\ServiceImportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Maatwebsite Excel adapter for service upsert imports.
 *
 * This class is a thin adapter that:
 * - Handles Maatwebsite Excel concerns (chunking, heading row, failures)
 * - Delegates business logic to ServiceImportProcessor
 * - Accumulates metrics in ServiceImportResult
 * - Uses batch lookup to avoid N+1 query problem
 *
 * Performance optimization:
 * - Validates all rows first, then executes batch INSERTs / UPDATEs
 * - Creates use two bulk INSERTs (catalog_items + service_details) instead of
 *   individual Eloquent calls that trigger the ServiceObserver per row
 * - Updates use DB::table() to avoid observer overhead
 * - Reduces ~1000 queries per 500-row chunk down to ~6 queries
 *
 * Transaction behavior:
 * - Each chunk is wrapped in its own DB::transaction() for chunk-level atomicity
 * - Reduces lock contention and allows partial success (successful chunks persist even if later chunks fail)
 * - Validation errors are caught and rows marked as failed (best-effort within chunk transaction)
 * - Recommended for imports >1000 rows to avoid long locks
 *
 * Upsert resolution policy:
 * - If name matches an existing service in DB -> update
 * - Otherwise -> create
 *
 * @see ServiceImportProcessor::processRow()
 * @see ServiceImportService::findExistingServicesInBatch()
 *
 * @codeCoverageIgnore
 */
class ServicesUpsertImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow
{
    use Importable, SkipsFailures {
        SkipsFailures::onFailure as traitOnFailure;
    }

    private ServiceImportResult $result;

    /** @var array<string, bool> Names already seen in the entire file (cross-chunk dedup) */
    private array $processedNames = [];

    /** @var array<int, bool> IDs already seen in the entire file (cross-chunk dedup) */
    private array $processedIds = [];

    public function __construct(
        private ServiceImportProcessor $processor,
        private ServiceImportService $importService,
        private int $chunkSize = 500,
        private ?ProcessJob $processJob = null,
    ) {
        $this->result = new ServiceImportResult;
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Processes a chunk of rows.
     *
     * Duplicate resolution (across entire file, not just per-chunk):
     * - Row whose ID was already processed -> skip
     * - Row whose name was already processed -> skip
     * - Row whose name matches an existing service in DB -> update
     * - Otherwise -> create
     *
     * Each chunk is processed within a single transaction for atomicity.
     */
    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            $this->processChunk($rows);
        });

        $this->processJob?->incrementProgress();
    }

    protected function processChunk(Collection $rows): void
    {
        $rowsArray = $rows->toArray();
        $nameMatchMap = $this->importService->findExistingServicesByNameInBatch($rowsArray);

        /** @var array<int, array{data: array, rowNumber: int}> Rows to create */
        $rowsToCreate = [];

        /** @var array<int, array{service: \App\Models\Service, data: array, rowNumber: int}> Rows to update */
        $rowsToUpdate = [];

        // Phase 1: Validate all rows and classify as create / update / skip / failed
        foreach ($rows as $index => $row) {
            $rowData = is_array($row) ? $row : $row->toArray();

            if ($this->isRowEmpty($rowData)) {
                continue;
            }

            $rowNumber = $index + 2;
            $normalized = $this->importService->normalizeRow($rowData);
            $rawName = trim((string) ($normalized['name'] ?? ''));
            $lowerName = mb_strtolower($rawName);
            $rowId = $normalized['id'] ?? null;

            // 1) Skip if the same ID already appeared anywhere in the file
            if ($rowId !== null && isset($this->processedIds[$rowId])) {
                $this->result->recordRowResult(ServiceImportRowResult::skipped());

                continue;
            }

            // 2) Skip if the same name already appeared anywhere in the file
            if ($lowerName !== '' && isset($this->processedNames[$lowerName])) {
                $this->result->recordRowResult(ServiceImportRowResult::skipped());

                continue;
            }

            // 3) If name matches an existing service in DB -> update
            $existingByName = $nameMatchMap[$index] ?? null;

            if ($existingByName !== null) {
                try {
                    $validatedRow = $this->importService->validateRow($rowData, $existingByName);
                    $rowsToUpdate[] = ['service' => $existingByName, 'data' => $validatedRow, 'rowNumber' => $rowNumber];
                } catch (ValidationException $e) {
                    $this->result->recordRowResult(ServiceImportRowResult::failed($e->errors()));
                    $this->handleFailure($rowNumber, $rowData, $e->errors());
                }

                $this->markProcessed($rowId, $lowerName);

                continue;
            }

            // 4) Create new service
            try {
                $validatedRow = $this->importService->validateRow($rowData, null);
                $rowsToCreate[] = ['data' => $validatedRow, 'rowNumber' => $rowNumber];
            } catch (ValidationException $e) {
                $this->result->recordRowResult(ServiceImportRowResult::failed($e->errors()));
                $this->handleFailure($rowNumber, $rowData, $e->errors());
            }

            $this->markProcessed($rowId, $lowerName);
        }

        // Phase 2: Batch create (bulk INSERT catalog_items + service_details)
        $this->batchCreate($rowsToCreate);

        // Phase 3: Batch update (bulk UPDATE catalog_items + service_details)
        $this->batchUpdate($rowsToUpdate);
    }

    private function markProcessed(?int $id, string $lowerName): void
    {
        if ($id !== null) {
            $this->processedIds[$id] = true;
        }

        if ($lowerName !== '') {
            $this->processedNames[$lowerName] = true;
        }
    }

    /**
     * Bulk-insert new services bypassing Eloquent observers.
     * Uses two batch INSERTs instead of N individual creates.
     *
     * @param  array<int, array{data: array, rowNumber: int}>  $rowsToCreate
     */
    private function batchCreate(array $rowsToCreate): void
    {
        if ($rowsToCreate === []) {
            return;
        }

        $now = now();

        // Build catalog_items bulk payload
        $catalogItemsData = [];
        foreach ($rowsToCreate as $entry) {
            $data = $entry['data'];
            $catalogItemsData[] = [
                'type' => CatalogItemType::SERVICE->value,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'cost' => 0,
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'COP',
                'is_active' => $data['is_active'] ?? true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Get max ID before insert (to find new IDs later within the transaction)
        $maxIdBefore = (int) DB::table('catalog_items')->max('id');

        // Bulk INSERT catalog_items
        DB::table('catalog_items')->insert($catalogItemsData);

        // Retrieve the new IDs (safe within transaction)
        $newIds = DB::table('catalog_items')
            ->where('id', '>', $maxIdBefore)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        // Build service_details bulk payload
        $serviceDetailsData = [];
        foreach ($rowsToCreate as $i => $entry) {
            $data = $entry['data'];
            $serviceDetailsData[] = [
                'catalog_item_id' => $newIds[$i],
                'duration' => $data['duration'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk INSERT service_details
        DB::table('service_details')->insert($serviceDetailsData);

        // Record results
        foreach ($rowsToCreate as $entry) {
            $this->result->recordRowResult(ServiceImportRowResult::created());
        }
    }

    /**
     * Batch-update existing services using a single CASE WHEN SQL per column.
     * Reduces N×2 individual UPDATE queries down to 2 queries regardless of chunk size.
     *
     * @param  array<int, array{service: \App\Models\Service, data: array, rowNumber: int}>  $rowsToUpdate
     */
    private function batchUpdate(array $rowsToUpdate): void
    {
        if ($rowsToUpdate === []) {
            return;
        }

        $now = now()->toDateTimeString();

        // Index data by catalog_item_id for single-query bulk update
        $catalogItems = [];
        $serviceUpsertRows = [];

        foreach ($rowsToUpdate as $entry) {
            $service = $entry['service'];
            $data = $entry['data'];
            $cid = $service->catalog_item_id;

            $catalogItems[$cid] = [
                'name' => $data['name'],
                'description' => $data['description'] ?? $service->description,
                'price' => $data['price'],
                'currency' => $data['currency'] ?? $service->currency,
                'is_active' => (int) ($data['is_active'] ?? $service->is_active),
            ];

            $serviceUpsertRows[] = [
                'catalog_item_id' => $cid,
                'duration' => $data['duration'] ?? $service->duration,
                'updated_at' => $now,
            ];
        }

        $ids = array_keys($catalogItems);
        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));

        // Build a single UPDATE catalog_items using CASE WHEN per column
        $columns = ['name', 'description', 'price', 'currency', 'is_active'];
        $setClauses = [];
        $bindings = [];

        foreach ($columns as $col) {
            $cases = implode(' ', array_fill(0, count($ids), 'WHEN ? THEN ?'));
            $setClauses[] = sprintf('%s = CASE id %s END', $col, $cases);

            foreach ($ids as $id) {
                $bindings[] = $id;
                $bindings[] = $catalogItems[$id][$col];
            }
        }

        $setClauses[] = 'updated_at = ?';
        $bindings[] = $now;

        // WHERE IN bindings
        foreach ($ids as $id) {
            $bindings[] = $id;
        }

        DB::statement(
            sprintf('UPDATE catalog_items SET %s WHERE id IN (%s)', implode(', ', $setClauses), $idPlaceholders),
            $bindings
        );

        // Single upsert for service_details (PK = catalog_item_id)
        DB::table('service_details')->upsert(
            $serviceUpsertRows,
            ['catalog_item_id'],
            ['duration', 'updated_at']
        );

        foreach ($rowsToUpdate as $_) {
            $this->result->recordRowResult(ServiceImportRowResult::updated());
        }
    }

    private function isRowEmpty(array $rowData): bool
    {
        foreach ($rowData as $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return false;
        }

        return true;
    }

    private function handleFailure(int $rowNumber, array $rowData, array $errors): void
    {
        foreach ($errors as $attribute => $messages) {
            $this->onFailure(new Failure(
                $rowNumber,
                $attribute,
                is_array($messages) ? $messages : [$messages],
                $rowData
            ));
        }
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->result->addFailure($failure);
        }

        $this->traitOnFailure(...$failures);
    }

    public function result(): ServiceImportResult
    {
        return $this->result;
    }
}
