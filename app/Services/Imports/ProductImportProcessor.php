<?php

namespace App\Services\Imports;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class ProductImportProcessor
{
    public function __construct(
        private readonly ProductImportService $importService
    ) {}

    /**
     * Procesa una fila individual de la importación.
     */
    public function processRow(array $row, ?Product $existingProduct = null): ProductImportRowResult
    {
        try {
            if ($existingProduct === null) {
                $existingProduct = $this->importService->findExistingProduct($row);
            }

            $validated = $this->importService->validateRow($row, $existingProduct);

            if ($existingProduct) {
                DB::transaction(function () use ($existingProduct, $validated) {
                    $updateData = array_filter($validated, fn ($v) => $v !== null);
                    if (! empty($updateData)) {
                        $existingProduct->update($updateData);
                    }
                });

                return ProductImportRowResult::updated();
            }

            DB::transaction(function () use ($validated) {
                $createData = array_filter($validated, fn ($value) => $value !== null);
                Product::create($createData);
            });

            return ProductImportRowResult::created();
        } catch (ValidationException $ex) {
            return ProductImportRowResult::failed($ex->errors());
        } catch (Throwable $th) {
            return ProductImportRowResult::failed([
                'import' => [$th->getMessage()],
            ]);
        }
    }
}
