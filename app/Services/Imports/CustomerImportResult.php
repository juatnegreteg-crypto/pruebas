<?php

namespace App\Services\Imports;

use Maatwebsite\Excel\Validators\Failure;

/**
 * Accumulates import processing metrics and errors.
 *
 * This is not a pure DTO - it contains deduplication logic to ensure
 * a row with multiple validation errors is only counted once as failed.
 */
class CustomerImportResult
{
    private int $totalRows = 0;

    private int $created = 0;

    private int $updated = 0;

    private int $failed = 0;

    private array $errors = [];

    /** @var array<int,bool> */
    private array $failedRows = [];

    public function incrementTotalRows(): void
    {
        $this->totalRows++;
    }

    public function incrementCreated(): void
    {
        $this->created++;
    }

    public function incrementUpdated(): void
    {
        $this->updated++;
    }

    /**
     * Records the result of processing a single row.
     * Encapsulates the decision logic for incrementing appropriate counters.
     */
    public function recordRowResult(CustomerImportRowResult $rowResult): void
    {
        $this->incrementTotalRows();

        if ($rowResult->isCreated()) {
            $this->incrementCreated();
        } elseif ($rowResult->isUpdated()) {
            $this->incrementUpdated();
        }
        // Note: Failed rows are recorded via addFailure() which is called separately
        // This is because Maatwebsite Excel's SkipsOnFailure trait requires Failure objects
    }

    public function addFailure(Failure $failure): void
    {
        $row = $failure->row();

        if (! isset($this->errors[$row])) {
            $this->errors[$row] = [];
        }

        $this->errors[$row][] = [
            'attribute' => $failure->attribute(),
            'messages' => $failure->errors(),
            'values' => $failure->values(),
        ];

        if (! isset($this->failedRows[$row])) {
            $this->failedRows[$row] = true;
            $this->failed++;
        }
    }

    public function totalRows(): int
    {
        return $this->totalRows;
    }

    public function created(): int
    {
        return $this->created;
    }

    public function updated(): int
    {
        return $this->updated;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    /**
     * Detalle de errores por fila.
     *
     * @return array<int,array<int,array<string,mixed>>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows(),
            'created' => $this->created(),
            'updated' => $this->updated(),
            'failed' => $this->failed(),
            'errors' => $this->errors(),
        ];
    }
}
