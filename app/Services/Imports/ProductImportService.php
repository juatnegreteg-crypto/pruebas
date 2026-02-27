<?php

namespace App\Services\Imports;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Support\ExcelFileValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/**
 * @codeCoverageIgnore
 */
class ProductImportService
{
    protected ExcelFileValidator $fileValidator;

    public function __construct(?ExcelFileValidator $fileValidator = null)
    {
        $this->fileValidator = $fileValidator ?? new ExcelFileValidator;
    }

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    public function requiredHeaders(): array
    {
        $columns = config('imports.product.columns');
        $required = Arr::except($columns, ['cost', 'unit']);

        return array_map(
            fn (array $aliases) => $aliases[0],
            $required
        );
    }

    public function validateFile(UploadedFile $file): void
    {
        $this->fileValidator->validate($file, $this->requiredHeaders());
    }

    public function normalize(array $row): array
    {
        $row = $this->mapHeaders($row);

        $val = fn ($k) => $row[$k] ?? null;

        return [
            'sku' => trim((string) ($val('sku') ?? '')),
            'name' => trim((string) ($val('name') ?? '')),
            'description' => ($v = $val('description')) !== null && trim((string) $v) !== '' ? (string) $v : null,
            'cost' => $this->toNumeric($val('cost')),
            'price' => $this->toNumeric($val('price')),
            'currency' => ($v = $val('currency')) ? strtoupper(trim((string) $v)) : 'COP',
            'unit' => ($v = $val('unit')) ? trim((string) $v) : 'unit',
            'isActive' => $this->toBool($val('is_active')),
            'stock' => $this->toInt($val('stock')),
        ];
    }

    private function mapHeaders(array $row): array
    {
        $columnMap = config('imports.product.columns');
        $mapped = [];

        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));

            foreach ($columnMap as $internalKey => $aliases) {
                if (in_array($normalizedKey, $aliases, true)) {
                    $mapped[$internalKey] = $value;
                    break;
                }
            }
        }

        return $mapped;
    }

    public function findExistingProduct(array $row): ?Product
    {
        $payload = $this->normalize($row);
        if ($payload['sku'] !== '') {
            return Product::where('sku', $payload['sku'])->first();
        }

        return null;
    }

    public function filterNullsForUpdate(array $data): array
    {
        return array_filter($data, fn ($v) => $v !== null);
    }

    public function findExistingProductsInBatch(array $rows): array
    {
        $indexToSku = [];
        foreach ($rows as $i => $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku !== '') {
                $indexToSku[$i] = $sku;
            }
        }

        $bySku = [];
        if ($indexToSku) {
            $values = array_values($indexToSku);
            // Build a case-insensitive query: normal IN + LOWER(...) IN (...) fallback
            $lowerValues = array_map('strtolower', $values);

            $placeholders = implode(',', array_fill(0, count($lowerValues), '?'));

            $products = Product::query()
                ->whereIn('sku', $values)
                ->orWhereRaw("LOWER(sku) IN ({$placeholders})", $lowerValues)
                ->get();

            foreach ($products as $p) {
                $bySku[strtolower((string) $p->sku)] = $p;
            }
        }

        $result = [];
        foreach ($rows as $i => $row) {
            $skuKey = strtolower(trim((string) ($row['sku'] ?? '')));
            $result[$i] = $skuKey !== '' && isset($bySku[$skuKey]) ? $bySku[$skuKey] : null;
        }

        return $result;
    }

    public function validateRow(array $row, ?Product $existing = null): array
    {
        $payload = $this->normalize($row);

        $rules = $this->getValidationRules($existing);

        // Reglas mínimas de importación (sin duplicar lógica del módulo):
        $rules = array_merge($rules, [
            'sku' => ['required', 'string', 'max:64'],
            'stock' => $existing ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'isActive' => $existing ? ['nullable', 'boolean'] : ['required', 'boolean'],
            'unit' => $existing ? ['nullable', 'string'] : ['required', 'string'],

        ]);

        $messages = $this->getValidationMessages($existing);

        $messages = [
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock debe ser mayor o igual a 0.',
        ];

        $validated = Validator::make($payload, $rules, $messages)->validate();

        if (array_key_exists('isActive', $validated)) {
            $validated['is_active'] = $validated['isActive'];
            unset($validated['isActive']);
        }

        return $validated;
    }

    /*
    |--------------------------------------------------------------------------
    | Protected Helpers
    |--------------------------------------------------------------------------
    */

    protected function getValidationRules(?Product $existing): array
    {
        $request = $existing
            ? $this->resolveFormRequest(UpdateProductRequest::class, $existing)
            : $this->resolveFormRequest(StoreProductRequest::class);

        return $request->rules();
    }

    protected function getValidationMessages(?Product $existing): array
    {
        $request = $existing
            ? $this->resolveFormRequest(UpdateProductRequest::class, $existing)
            : $this->resolveFormRequest(StoreProductRequest::class);

        return method_exists($request, 'messages') ? $request->messages() : [];
    }

    protected function resolveFormRequest(string $class, ?Product $product = null): FormRequest
    {
        /** @var FormRequest $request */
        $request = new $class;

        if ($product) {
            $request->setRouteResolver(fn () => new class($product)
            {
                public function __construct(private Product $product) {}

                public function parameter(string $key, $default = null)
                {
                    return $key === 'product' ? $this->product : $default;
                }

                public function parameters(): array
                {
                    return ['product' => $this->product];
                }
            });
        }

        return $request;
    }

    /*
    |--------------------------------------------------------------------------
    | Private Data Converters
    |--------------------------------------------------------------------------
    */

    private function toNumeric(mixed $v): mixed
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = is_string($v) ? str_replace(',', '.', trim($v)) : $v;

        return is_numeric($s) ? (float) $s : null;
    }

    private function toInt(mixed $v): mixed
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            $i = (int) $v;

            return $i >= 0 ? $i : null;
        }

        return null;
    }

    private function toBool(mixed $v): ?bool
    {
        if ($v === null) {
            return null;
        }

        $mapTrue = ['true', '1', 'si', 'sí', 'activo', 'on', 'yes'];
        $mapFalse = ['false', '0', 'no', 'inactivo', 'off'];

        $s = strtolower(trim((string) $v));

        if (in_array($s, $mapTrue, true)) {
            return true;
        }
        if (in_array($s, $mapFalse, true)) {
            return false;
        }
        if (is_bool($v)) {
            return $v;
        }

        return null;
    }
}
