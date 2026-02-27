<?php

namespace App\Imports;

use App\Services\Imports\ProductImportProcessor;
use App\Services\Imports\ProductImportResult;
use App\Services\Imports\ProductImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;

class ProductsUpsertImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow
{
    use Importable, SkipsFailures { SkipsFailures::onFailure as traitOnFailure; }

    private ProductImportResult $result;

    public function __construct(
        private ProductImportProcessor $processor,
        private ProductImportService $importService,
        private int $chunkSize = 500
    ) {
        $this->result = new ProductImportResult;
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    public function collection(Collection $rows): void
    {
        $rowsArray = $rows->toArray();
        $existingMap = $this->importService->findExistingProductsInBatch($rowsArray);
        // Detectar SKUs duplicados dentro del mismo archivo y marcar las filas
        $skuToIndexes = [];
        foreach ($rowsArray as $i => $r) {
            $sku = trim((string) ($r['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $skuToIndexes[$sku][] = $i;
        }

        $duplicateIndexes = [];
        foreach ($skuToIndexes as $sku => $indexes) {
            if (count($indexes) > 1) {
                foreach ($indexes as $i) {
                    $duplicateIndexes[$i] = $sku;
                }
            }
        }

        foreach ($rows as $index => $row) {
            $rowData = is_array($row) ? $row : $row->toArray();
            $rowNumber = $index + 2;

            // Si este índice está marcado como duplicado dentro del archivo,
            // registramos el fallo y evitamos procesar la fila.
            if (isset($duplicateIndexes[$index])) {
                $dupSku = $duplicateIndexes[$index];
                $message = "SKU duplicado en el archivo: {$dupSku}";

                $this->result->recordRowResult(
                    \App\Services\Imports\ProductImportRowResult::failed(['sku' => [$message]])
                );

                $this->handleFailure($rowNumber, $rowData, ['sku' => [$message]]);

                continue;
            }

            $existingProduct = $existingMap[$index] ?? null;

            $rowResult = $this->processor->processRow($rowData, $existingProduct);
            $this->result->recordRowResult($rowResult);

            if ($rowResult->isFailed()) {
                $this->handleFailure($rowNumber, $rowData, $rowResult->errors);
            }
        }
    }

    private function handleFailure(int $rowNumber, array $rowData, array $errors): void
    {
        foreach ($errors as $attribute => $messages) {
            $this->onFailure(new Failure($rowNumber, $attribute, is_array($messages) ? $messages : [$messages], $rowData));
        }
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->result->addFailure($failure);
        }
        $this->traitOnFailure(...$failures);
    }

    public function result(): ProductImportResult
    {
        return $this->result;
    }
}
