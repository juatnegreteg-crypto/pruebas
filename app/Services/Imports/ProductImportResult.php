<?php

namespace App\Services\Imports;

use Maatwebsite\Excel\Validators\Failure;

/**
 * @codeCoverageIgnore
 */
class ProductImportResult
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

    public function recordRowResult(ProductImportRowResult $rowResult): void
    {
        $this->incrementTotalRows();

        if ($rowResult->isCreated()) {
            $this->incrementCreated();
        } elseif ($rowResult->isUpdated()) {
            $this->incrementUpdated();
        }
    }

    public function addFailure(Failure $failure): void
    {
        $row = $failure->row();

        $this->errors[$row] ??= [];
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

    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'created' => $this->created,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'errors' => $this->errors,
        ];
    }
}
