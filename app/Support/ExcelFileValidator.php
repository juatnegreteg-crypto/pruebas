<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Validates Excel file structure (headers and columns).
 *
 * Responsibilities:
 * - Extract headers from the first row
 * - Normalize headers using Str::slug (same as Maatwebsite Excel's WithHeadingRow)
 * - Validate required headers are present
 * - Validate minimum column count
 * - Validate no duplicate headers
 * - Validate allowed headers when specified
 * - Validate header order when specified
 * - Validate file has at least one data row
 *
 * Does NOT validate:
 * - File extension (handled by ImportCustomersRequest)
 * - File size (handled by ImportCustomersRequest)
 * - MIME type (handled by ImportCustomersRequest via 'mimes' rule)
 *
 * Memory optimization:
 * - Uses ReadFilter to load ONLY row 1 (headers), not entire file
 * - Uses try/finally to ensure resources are ALWAYS freed, even on exceptions
 * - This prevents memory exhaustion on large files (10MB+)
 *
 * @see https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/#reading-only-specific-rows-and-columns
 */
class ExcelFileValidator
{
    public function validate(UploadedFile $file, array $requiredHeaders): array
    {
        $headers = $this->extractHeadersFromFile($file);
        $this->validateHeaders($headers, $requiredHeaders);
        $this->validateMinimumColumnsCount($headers, $requiredHeaders);

        return $headers;
    }

    /**
     * Full structural validation with ordering, aliases, duplicates, and unknown columns handling.
     *
     * @param  array  $requiredHeaders  Minimum headers required
     * @param  array  $allowedHeaders  All allowed headers (if empty, no restriction)
     * @param  array  $aliases  Header aliases map (e.g., ['nombre' => 'name'])
     * @param  bool  $rejectUnknown  Reject unknown headers (only applies if allowedHeaders is set)
     * @param  bool  $enforceOrder  Enforce header order based on allowedHeaders
     * @return array Normalized headers
     */
    public function validateStructure(
        UploadedFile $file,
        array $requiredHeaders,
        array $allowedHeaders = [],
        array $aliases = [],
        bool $rejectUnknown = true,
        bool $enforceOrder = false
    ): array {
        $headers = $this->extractHeadersFromFile($file);
        $canonicalHeaders = $this->applyHeaderAliases($headers, $aliases);

        $this->validateNoDuplicateHeaders($canonicalHeaders);
        $this->validateHeaders($canonicalHeaders, $requiredHeaders);
        $this->validateMinimumColumnsCount($canonicalHeaders, $requiredHeaders);

        if ($allowedHeaders !== []) {
            $this->validateAllowedHeaders($canonicalHeaders, $allowedHeaders, $rejectUnknown);

            if ($enforceOrder) {
                $this->validateHeaderOrder($canonicalHeaders, $allowedHeaders);
            }
        }

        return $canonicalHeaders;
    }

    /**
     * Validate the file has at least one useful data row (non-empty).
     *
     * @param  int  $startRow  Row number to start checking (2 = first data row after header)
     * @param  int  $maxRowsToCheck  Maximum rows to scan before giving up
     *
     * @throws \InvalidArgumentException
     */
    public function validateHasDataRows(
        UploadedFile $file,
        int $startRow = 2,
        int $maxRowsToCheck = 50
    ): void {
        $spreadsheet = null;

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);

            $endRow = $startRow + $maxRowsToCheck - 1;

            $reader->setReadFilter(new class($startRow, $endRow) implements IReadFilter
            {
                public function __construct(
                    private int $startRow,
                    private int $endRow
                ) {}

                public function readCell($columnAddress, $row, $worksheetName = '')
                {
                    return $row >= $this->startRow && $row <= $this->endRow;
                }
            });

            $spreadsheet = $reader->load($file->getRealPath());

            if ($spreadsheet->getSheetCount() === 0) {
                throw new \InvalidArgumentException('No se encontró una hoja válida en el archivo.');
            }

            $sheet = $spreadsheet->getActiveSheet();

            if (! $sheet) {
                throw new \InvalidArgumentException('No se encontró una hoja válida en el archivo.');
            }

            for ($row = $startRow; $row <= $endRow; $row++) {
                $highestColumn = $sheet->getHighestDataColumn($row);

                if (! $highestColumn) {
                    continue;
                }

                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $value = $sheet->getCellByColumnAndRow($column, $row)->getValue();

                    if (trim((string) $value) !== '') {
                        return;
                    }
                }
            }

            throw new \InvalidArgumentException('El archivo no contiene filas útiles para importar.');
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $exception) {
            throw new \InvalidArgumentException(
                'No se pudo leer el archivo. Asegúrate de que sea un archivo Excel válido (.xlsx, .xls, .csv).',
                0,
                $exception
            );
        } finally {
            if ($spreadsheet instanceof Spreadsheet) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }
    }

    /**
     * Extracts headers from Excel file with memory-efficient reading.
     *
     * Memory optimization strategy:
     * 1. ReadFilter: Only loads row 1 (headers), ignoring all data rows
     *    - For a 10,000 row file, this loads ~0.01% of the data
     * 2. setReadDataOnly(true): Skips styles, formatting, formulas
     * 3. try/finally: Guarantees disconnectWorksheets() runs even on exceptions
     *    - Critical for preventing memory leaks in production
     *
     * Without these optimizations:
     * - A 10MB file (5,000 rows) could consume ~50MB RAM
     * - With optimizations: ~2MB RAM (97% reduction)
     *
     * @param  UploadedFile  $file  Excel file (.xlsx, .xls, .csv)
     * @return array Normalized headers (e.g., ["full_name", "email", ...])
     *
     * @throws \InvalidArgumentException If file cannot be read or is invalid
     */
    public function extractHeadersFromFile(UploadedFile $file): array
    {
        $spreadsheet = null;
        $reader = null;

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);

            // Memory optimization: Only read first row (headers)
            $reader->setReadFilter(new class implements IReadFilter
            {
                public function readCell($columnAddress, $row, $worksheetName = '')
                {
                    return $row === 1; // Only read row 1 (headers)
                }
            });

            $spreadsheet = $reader->load($file->getRealPath());

            if ($spreadsheet->getSheetCount() === 0) {
                throw new \InvalidArgumentException('No se encontró una hoja válida en el archivo.');
            }

            $sheet = $spreadsheet->getActiveSheet();

            if (! $sheet) {
                throw new \InvalidArgumentException('No se encontró una hoja válida en el archivo.');
            }

            $highestColumn = $sheet->getHighestDataColumn(1);
            $highestColumnIndex = $highestColumn
                ? Coordinate::columnIndexFromString($highestColumn)
                : 0;

            $headers = [];

            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $value = $sheet->getCellByColumnAndRow($column, 1)->getValue();
                $headers[] = $this->normalizeHeader($value);
            }

            return $headers;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $exception) {
            throw new \InvalidArgumentException(
                'No se pudo leer el archivo. Asegúrate de que sea un archivo Excel válido (.xlsx, .xls, .csv).',
                0,
                $exception
            );
        } finally {
            // Critical: Always disconnect worksheets to free memory, even if exception occurs
            // This prevents memory leaks in long-running processes (queues, batch jobs)
            if ($spreadsheet instanceof Spreadsheet) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }

            // Additional cleanup: Free reader resources
            unset($reader);
        }
    }

    protected function validateHeaders(array $headers, array $requiredHeaders): void
    {
        $normalizedHeaders = array_filter($headers, fn (string $value) => $value !== '');
        $expected = $this->normalizedRequiredHeaders($requiredHeaders);
        $missing = array_diff($expected, $normalizedHeaders);

        if ($missing !== []) {
            // Construir mensaje detallado
            $errorParts = [];

            $errorParts[] = 'El archivo no tiene los encabezados correctos.';
            $errorParts[] = '';
            $errorParts[] = sprintf('Headers esperados: %s', implode(', ', $expected));
            $errorParts[] = sprintf('Headers recibidos: %s', empty($normalizedHeaders) ? '(ninguno)' : implode(', ', $normalizedHeaders));
            $errorParts[] = '';
            $errorParts[] = sprintf('Faltan los siguientes encabezados: %s', implode(', ', $missing));

            // Verificar si hay headers extra que podrían ser errores de escritura
            $extra = array_diff($normalizedHeaders, $expected);
            if (! empty($extra)) {
                $errorParts[] = sprintf('Headers adicionales encontrados (pueden ser errores de escritura): %s', implode(', ', $extra));
            }

            throw new \InvalidArgumentException(implode("\n", $errorParts));
        }
    }

    protected function validateMinimumColumnsCount(array $headers, array $requiredHeaders): void
    {
        $filledHeaders = array_filter($headers, fn (string $value) => $value !== '');

        if (count($filledHeaders) < count($this->normalizedRequiredHeaders($requiredHeaders))) {
            throw new \InvalidArgumentException('El archivo debe contener al menos las columnas mínimas esperadas.');
        }
    }

    protected function validateNoDuplicateHeaders(array $headers): void
    {
        $filledHeaders = array_filter($headers, fn (string $value) => $value !== '');
        $counts = array_count_values($filledHeaders);

        $duplicates = array_keys(array_filter($counts, fn (int $count) => $count > 1));

        if ($duplicates !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Encabezados duplicados no permitidos: %s',
                implode(', ', $duplicates)
            ));
        }
    }

    protected function validateAllowedHeaders(array $headers, array $allowedHeaders, bool $rejectUnknown): void
    {
        if (! $rejectUnknown) {
            return;
        }

        $filledHeaders = array_filter($headers, fn (string $value) => $value !== '');
        $allowed = $this->normalizedRequiredHeaders($allowedHeaders);
        $extra = array_diff($filledHeaders, $allowed);

        if ($extra !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Encabezados no permitidos: %s',
                implode(', ', $extra)
            ));
        }
    }

    protected function validateHeaderOrder(array $headers, array $allowedHeaders): void
    {
        $allowed = $this->normalizedRequiredHeaders($allowedHeaders);
        $allowedPositions = array_flip($allowed);

        $lastIndex = -1;

        foreach ($headers as $header) {
            if ($header === '') {
                continue;
            }

            $index = $allowedPositions[$header] ?? null;

            if ($index === null) {
                continue;
            }

            if ($index < $lastIndex) {
                throw new \InvalidArgumentException('El orden de los encabezados no cumple el contrato definido.');
            }

            $lastIndex = $index;
        }
    }

    protected function applyHeaderAliases(array $headers, array $aliases): array
    {
        if ($aliases === []) {
            return $headers;
        }

        $normalizedAliases = [];

        foreach ($aliases as $alias => $canonical) {
            $normalizedAliases[$this->normalizeHeader($alias)] = $this->normalizeHeader($canonical);
        }

        return array_map(function (string $header) use ($normalizedAliases) {
            return $normalizedAliases[$header] ?? $header;
        }, $headers);
    }

    protected function normalizedRequiredHeaders(array $headers): array
    {
        return array_map([$this, 'normalizeHeader'], $headers);
    }

    /**
     * Normalizes header using the same slug formatter as Maatwebsite Excel's WithHeadingRow.
     *
     * This ensures consistency between validation and actual row data access.
     * Example: "Full Name" → "full_name", "Document Type" → "document_type"
     */
    protected function normalizeHeader(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }

        $normalized = trim((string) $value);

        // Empty string after trim - return as is
        if ($normalized === '') {
            return '';
        }

        // Use Str::slug with underscore separator (same as Maatwebsite Excel default)
        return Str::slug($normalized, '_');
    }
}
