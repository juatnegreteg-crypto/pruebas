<?php

namespace App\Services\Imports;

use Maatwebsite\Excel\Validators\Failure;

/**
 * Accumulates import results and errors per row.
 *
 * Error format per row:
 * [
 *   ['fila' => 2, 'campo' => 'price', 'valor' => 'abc', 'mensaje' => 'The price field must be a number.'],
 *   ['fila' => 3, 'campo' => 'name', 'valor' => null, 'mensaje' => 'The name field is required.'],
 * ]
 *
 * @codeCoverageIgnore
 */
class ServiceImportResult
{
    /**
     * Maximum number of error entries stored in memory and returned in the response.
     * When exceeded, newer errors are counted but not stored (errors_truncated = true).
     */
    private const MAX_ERRORS = 500;

    private int $totalRows = 0;

    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $failed = 0;

    private array $errors = [];

    private int $totalErrors = 0;

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

    public function incrementSkipped(): void
    {
        $this->skipped++;
    }

    /**
     * Records the result of processing a single row.
     * Encapsulates the decision logic for incrementing appropriate counters.
     */
    public function recordRowResult(ServiceImportRowResult $rowResult): void
    {
        $this->incrementTotalRows();

        if ($rowResult->isCreated()) {
            $this->incrementCreated();
        } elseif ($rowResult->isUpdated()) {
            $this->incrementUpdated();
        } elseif ($rowResult->isSkipped()) {
            $this->incrementSkipped();
        }
    }

    public function addFailure(Failure $failure): void
    {
        $row = $failure->row();
        $field = $failure->attribute();
        $values = $failure->values();
        $value = is_array($values) ? ($values[$field] ?? null) : null;

        foreach ($failure->errors() as $message) {
            $this->totalErrors++;

            if (count($this->errors) < self::MAX_ERRORS) {
                $this->errors[] = [
                    'fila' => $row,
                    'campo' => $field,
                    'valor' => $value,
                    'mensaje' => $message,
                ];
            }
        }

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

    public function skipped(): int
    {
        return $this->skipped;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function totalErrors(): int
    {
        return $this->totalErrors;
    }

    public function errorsAreTruncated(): bool
    {
        return $this->totalErrors > self::MAX_ERRORS;
    }

    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows(),
            'created' => $this->created(),
            'updated' => $this->updated(),
            'skipped' => $this->skipped(),
            'failed' => $this->failed(),
            'errors' => $this->errors(),
            'total_errors' => $this->totalErrors(),
            'errors_truncated' => $this->errorsAreTruncated(),
        ];
    }
}
