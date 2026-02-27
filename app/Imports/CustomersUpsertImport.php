<?php

namespace App\Imports;

use App\Services\Imports\CustomerImportProcessor;
use App\Services\Imports\CustomerImportResult;
use App\Services\Imports\CustomerImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Maatwebsite Excel adapter for customer imports (upsert).
 *
 * Architecture pattern:
 * - This class is a MINIMAL ADAPTER between Maatwebsite Excel and our domain logic
 * - All business logic is delegated to CustomerImportProcessor (single entry point)
 * - CustomerImportService provides batch optimization and support services
 * - CustomerImportResult accumulates metrics
 *
 * Responsibilities (minimal orchestration only):
 * - Receive chunks from Maatwebsite Excel
 * - Batch-load existing customers (2 queries per chunk)
 * - Delegate row processing to CustomerImportProcessor
 * - Accumulate results in CustomerImportResult
 * - Handle Maatwebsite's SkipsOnFailure contract
 *
 * Performance optimization:
 * - Uses findExistingCustomersInBatch() to reduce queries from N*2 to 2 per chunk
 * - Configurable chunk size (default 500)
 *
 * Transaction strategy: BEST-EFFORT (by design)
 * ================================================
 * This import uses a "best-effort" approach where each row is processed independently:
 *
 * ✅ Why best-effort is the RIGHT choice for imports:
 * 1. User experience: One invalid row shouldn't reject 499 valid rows
 * 2. Data recovery: Users can fix failed rows and re-import (upsert handles duplicates)
 * 3. Debugging: Users get detailed errors for EACH failed row, not just "chunk failed"
 * 4. Incremental progress: Large files can be imported in parts without losing work
 * 5. Business logic: Customer creation is idempotent (by document_number/email)
 *
 * Each row write is atomic at database statement level (single INSERT/UPDATE operation).
 * Rows within a chunk are NOT wrapped in a single transaction together.
 *
 * ❌ Why "all-or-nothing per chunk" would be WRONG:
 * - One validation error in row 499 would rollback 498 valid customers
 * - Users would have to fix ALL errors before ANY data is imported
 * - No partial progress feedback (all 10,000 rows succeed or all fail)
 * - Re-importing the file after fixing errors would retry successful rows unnecessarily
 *
 * Result format:
 * {
 *   "total_rows": 500,
 *   "created": 450,
 *   "updated": 40,
 *   "failed": 10,
 *   "errors": { 23: [...], 45: [...], ... }  // Only failed rows with detailed errors
 * }
 *
 * @see CustomerImportProcessor::processRow() - Single entry point for row processing logic
 * @see CustomerImportService::findExistingCustomersInBatch() - Batch optimization
 * @see CustomerService::createCustomer() - Row persistence
 * @see CustomerService::updateCustomer() - Row persistence
 */
class CustomersUpsertImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow
{
    use Importable, SkipsFailures {
        SkipsFailures::onFailure as traitOnFailure;
    }

    private CustomerImportResult $result;

    public function __construct(
        private CustomerImportProcessor $processor,
        private CustomerImportService $importService,
        private int $chunkSize = 500
    ) {
        $this->result = new CustomerImportResult;
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Procesa un chunk completo de filas usando batch lookup.
     * Reduce queries de ~N*2 a solo 2 por chunk (whereIn para document_number y email).
     *
     * This adapter delegates all business logic to CustomerImportProcessor
     * and metrics recording to CustomerImportResult.
     */
    public function collection(Collection $rows): void
    {
        // Convertir collection a array para indexación
        $rowsArray = $rows->toArray();

        // Batch lookup: 2 queries para todo el chunk
        $existingCustomersMap = $this->importService->findExistingCustomersInBatch($rowsArray);

        // Procesar cada fila con el cliente existente ya precargado
        foreach ($rows as $index => $row) {
            $rowData = is_array($row) ? $row : $row->toArray();
            // Row number en Excel = índice de collection + 2 (header + 0-indexed)
            $rowNumber = $index + 2;

            $existingCustomer = $existingCustomersMap[$index] ?? null;
            $rowResult = $this->processor->processRow($rowData, $existingCustomer);

            // Delegate metrics recording to result (encapsulates decision logic)
            $this->result->recordRowResult($rowResult);

            // Handle failures (required by Maatwebsite Excel's SkipsOnFailure trait)
            if ($rowResult->isFailed()) {
                $this->handleFailure($rowNumber, $rowData, $rowResult->errors);
            }
        }
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

    public function result(): CustomerImportResult
    {
        return $this->result;
    }
}
