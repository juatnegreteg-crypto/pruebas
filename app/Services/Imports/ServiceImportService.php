<?php

namespace App\Services\Imports;

use App\Enums\CatalogItemType;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\CatalogItem;
use App\Models\Service;
use App\Support\ExcelFileValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Defines the XLS contract for service imports.
 *
 * XLS Contract:
 * - Allowed headers: id, name, description, cost, price, currency, unit, is_active, duration
 * - Required per row: name, price
 * - Defaults: currency=COP, is_active=true, duration=0
 * - Update strategy: if name matches existing service -> update, else create
 * - Normalization: trim strings, currency uppercase, parse boolean (activo/inactivo), parse numeric
 *
 * @codeCoverageIgnore
 */
class ServiceImportService
{
    protected ExcelFileValidator $fileValidator;

    public function __construct(?ExcelFileValidator $fileValidator = null)
    {
        $this->fileValidator = $fileValidator ?? new ExcelFileValidator;
    }

    /**
     * Canonical list of allowed XLS headers (defines order and exhaustive list).
     */
    public function allowedHeaders(): array
    {
        return [
            'id',
            'name',
            'description',
            'cost',
            'price',
            'currency',
            'unit',
            'is_active',
            'duration',
        ];
    }

    /**
     * Minimum headers required for file validation (must be present).
     */
    public function requiredHeaders(): array
    {
        return [
            'name',
            'price',
        ];
    }

    /**
     * Column aliases: maps user-friendly Spanish headers to canonical DB headers.
     *
     * @return array<string,string>
     */
    public function headerAliases(): array
    {
        return [
            'nombre' => 'name',
            'descripcion' => 'description',
            'costo' => 'cost',
            'precio' => 'price',
            'moneda' => 'currency',
            'unidad' => 'unit',
            'activo' => 'is_active',
            'estado' => 'is_active',
            'duracion' => 'duration',
            'duracion_min' => 'duration',
        ];
    }

    /**
     * User-friendly headers for the downloadable template.
     *
     * @return array<string,string> canonical => display
     */
    public function templateHeaders(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'cost' => 'Costo',
            'price' => 'Precio',
            'currency' => 'Moneda',
            'unit' => 'Unidad',
            'is_active' => 'Estado',
            'duration' => 'Duración (min)',
        ];
    }

    /**
     * Generate a template spreadsheet with styled headers and an example row.
     */
    public function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Servicios');

        $headers = array_values($this->templateHeaders());

        foreach ($headers as $colIndex => $header) {
            $col = Coordinate::stringFromColumnIndex($colIndex + 1);
            $cell = "{$col}1";
            $sheet->setCellValue($cell, $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style header row
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD6E4F0');

        // Example row
        $example = [
            'Alineación y balanceo',
            'Alineación de ruedas con balanceo',
            '20000',
            '50000',
            'COP',
            'unit',
            'activo',
            '30',
        ];
        foreach ($example as $colIndex => $value) {
            $col = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue("{$col}2", $value);
        }

        return $spreadsheet;
    }

    /**
     * Filename for the downloadable template.
     */
    public function templateFilename(): string
    {
        return 'plantilla-servicios.xlsx';
    }

    /**
     * Validate file structure, headers, and data rows before processing.
     * This is called by the controller after FormRequest validation (extension/size/mime).
     *
     * Validates:
     * - File has a valid sheet
     * - Headers follow the defined contract (required/allowed/no duplicates/order)
     * - File has at least one data row
     *
     * @throws \InvalidArgumentException
     */
    public function validateFile(UploadedFile $file): void
    {
        $this->fileValidator->validateStructure(
            $file,
            $this->requiredHeaders(),
            $this->allowedHeaders(),
            $this->headerAliases(),
            rejectUnknown: true,
            enforceOrder: false
        );

        $this->fileValidator->validateHasDataRows($file);
    }

    /**
     * Validate a row using the import rules.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateRow(array $row, ?Service $existingService = null): array
    {
        $payload = $this->normalizeRow($row);
        $payload = $this->applyDefaults($payload, $existingService);

        unset($payload['id']);

        $rules = $this->getValidationRules($existingService);

        return Validator::make($payload, $rules)->validate();
    }

    /**
     * Lookup an existing service by id in the row.
     */
    public function findExistingService(array $row): ?Service
    {
        $payload = $this->normalizeRow($row);

        $id = $payload['id'] ?? null;
        if (! $id) {
            return null;
        }

        return Service::query()->find($id);
    }

    /**
     * Batch lookup existing services by id only.
     *
     * @return array<int,Service|null>
     */
    public function findExistingServicesByIdInBatch(array $rows): array
    {
        $ids = [];
        $normalizedRows = [];

        foreach ($rows as $index => $row) {
            $payload = $this->normalizeRow($row);
            $normalizedRows[$index] = $payload;

            if (! empty($payload['id'])) {
                $ids[$index] = $payload['id'];
            }
        }

        $servicesById = [];

        if ($ids !== []) {
            Service::query()
                ->whereKey(array_values($ids))
                ->get()
                ->each(function (Service $service) use (&$servicesById) {
                    $servicesById[$service->getKey()] = $service;
                });
        }

        $result = [];

        foreach ($rows as $index => $row) {
            $payload = $normalizedRows[$index];
            $id = $payload['id'] ?? null;

            $result[$index] = $id && isset($servicesById[$id])
                ? $servicesById[$id]
                : null;
        }

        return $result;
    }

    /**
     * Batch lookup existing services by name.
     * Returns the actual Service model for rows whose name matches an existing service.
     *
     * @return array<int,Service|null> index => Service if name exists in DB
     */
    public function findExistingServicesByNameInBatch(array $rows): array
    {
        $namesToLookup = [];

        foreach ($rows as $index => $row) {
            $payload = $this->normalizeRow($row);
            $name = $payload['name'] ?? null;

            if ($name !== null && $name !== '') {
                $namesToLookup[$index] = $name;
            }
        }

        $result = [];

        if ($namesToLookup !== []) {
            $uniqueRawNames = array_unique(array_values($namesToLookup));

            $servicesByName = [];
            CatalogItem::query()
                ->where('type', CatalogItemType::SERVICE)
                ->whereIn('name', $uniqueRawNames)
                ->with('service')
                ->get()
                ->each(function (CatalogItem $item) use (&$servicesByName) {
                    if ($item->service) {
                        $servicesByName[mb_strtolower($item->name)] = $item->service;
                    }
                });

            foreach ($namesToLookup as $index => $rawName) {
                $service = $servicesByName[mb_strtolower($rawName)] ?? null;

                if ($service !== null) {
                    $result[$index] = $service;
                }
            }
        }

        return $result;
    }

    /**
     * Normalize raw row data according to XLS contract.
     * Applies field-specific normalization (trim, uppercase, parse boolean/numeric).
     */
    public function normalizeRow(array $row): array
    {
        // Resolve aliases first (e.g. 'nombre' → 'name')
        $aliases = $this->headerAliases();
        $resolved = [];
        foreach ($row as $key => $value) {
            $canonical = $aliases[mb_strtolower(trim((string) $key))] ?? $key;
            $resolved[$canonical] = $value;
        }

        $payload = [];

        foreach ($this->allowedHeaders() as $header) {
            $payload[$header] = $resolved[$header] ?? null;
        }

        $payload['id'] = $this->normalizeId($payload['id']);
        $payload['name'] = $this->normalizeString($payload['name']);
        $payload['description'] = $this->normalizeString($payload['description']);
        $payload['cost'] = $this->normalizeDecimal($payload['cost']);
        $payload['price'] = $this->normalizeDecimal($payload['price']);
        $payload['currency'] = $this->normalizeCurrency($payload['currency']);
        $payload['unit'] = $this->normalizeString($payload['unit']);
        $payload['is_active'] = $this->normalizeBoolean($payload['is_active']);
        $payload['duration'] = $this->normalizeInteger($payload['duration']);

        return $payload;
    }

    /** @var array<string,array>|null */
    private ?array $createRules = null;

    /** @var array<string,array>|null */
    private ?array $updateRules = null;

    protected function getValidationRules(?Service $existingService): array
    {
        if ($existingService !== null) {
            if ($this->updateRules === null) {
                $request = $this->resolveFormRequest(UpdateServiceRequest::class);
                $this->updateRules = $this->extendImportRules($request->rules());
            }

            return $this->updateRules;
        }

        if ($this->createRules === null) {
            $request = $this->resolveFormRequest(StoreServiceRequest::class);
            $this->createRules = $this->extendImportRules($request->rules());
        }

        return $this->createRules;
    }

    protected function extendImportRules(array $rules): array
    {
        if (! array_key_exists('duration', $rules)) {
            $rules['duration'] = ['nullable', 'integer', 'min:0'];
        }

        $rules['is_active'] = ['required', 'boolean'];

        return $rules;
    }

    protected function resolveFormRequest(string $class): FormRequest
    {
        /** @var FormRequest $request */
        $request = new $class;

        return $request;
    }

    protected function applyDefaults(array $payload, ?Service $existingService = null): array
    {
        if ($payload['currency'] === null) {
            $payload['currency'] = $existingService?->currency ?? 'COP';
        }

        if ($payload['unit'] === null) {
            $payload['unit'] = $existingService?->unit?->value
                ?? $existingService?->unit
                ?? 'unit';
        }

        if ($payload['is_active'] === null) {
            $payload['is_active'] = $existingService?->is_active ?? true;
        }

        if ($payload['duration'] === null) {
            $payload['duration'] = $existingService?->duration ?? 0;
        }

        return $payload;
    }

    protected function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function normalizeCurrency(mixed $value): ?string
    {
        $string = $this->normalizeString($value);

        return $string === null ? null : strtoupper($string);
    }

    protected function normalizeBoolean(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : $value);
        }

        $string = strtolower(trim((string) $value));

        if ($string === '') {
            return null;
        }

        if (in_array($string, ['1', 'true', 'yes', 'y', 'si', 's', 'on', 'activo'], true)) {
            return true;
        }

        if (in_array($string, ['0', 'false', 'no', 'n', 'off', 'inactivo'], true)) {
            return false;
        }

        return $value;
    }

    protected function normalizeDecimal(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        $normalized = $this->normalizeNumericString($string);

        return is_numeric($normalized) ? (float) $normalized : $string;
    }

    protected function normalizeInteger(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        $normalized = $this->normalizeNumericString($string);

        if (preg_match('/^-?\d+$/', $normalized) !== 1) {
            return $string;
        }

        return (int) $normalized;
    }

    protected function normalizeId(mixed $value): ?int
    {
        $id = $this->normalizeInteger($value);

        if (! is_int($id) || $id <= 0) {
            return null;
        }

        return $id;
    }

    protected function normalizeNumericString(string $value): string
    {
        $normalized = str_replace(' ', '', $value);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);

            return $normalized;
        }

        if (str_contains($normalized, ',')) {
            return str_replace(',', '.', $normalized);
        }

        return $normalized;
    }
}
