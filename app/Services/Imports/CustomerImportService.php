<?php

namespace App\Services\Imports;

use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Support\CustomerNormalizer;
use App\Support\ExcelFileValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Service for customer import operations.
 *
 * Responsibilities:
 * - File validation (format and headers)
 * - Row validation (using FormRequests rules)
 * - Finding existing customers (single and batch lookup)
 * - Batch optimization for performance
 */
class CustomerImportService
{
    protected ExcelFileValidator $fileValidator;

    public function __construct(?ExcelFileValidator $fileValidator = null)
    {
        $this->fileValidator = $fileValidator ?? new ExcelFileValidator;
    }

    /**
     * Devuelve los encabezados canónicos que debe contener el archivo Excel.
     */
    public function requiredHeaders(): array
    {
        return [
            'Nombre completo',
            'Correo electrónico',
            'Tipo de documento',
            'Número de documento',
            'Teléfono',
            'Dirección',
            'Complemento',
            'Barrio',
            'Ciudad',
            'Departamento',
            'Código postal',
            'País',
            'Referencias',
        ];
    }

    /**
     * Valida que el archivo esté en un formato aceptado y que tenga los encabezados esperados.
     */
    public function validateFile(UploadedFile $file): void
    {
        $this->fileValidator->validate($file, $this->requiredHeaders());
    }

    public function shouldQueueImport(UploadedFile $file): bool
    {
        $queueThresholdKb = (int) config('imports.customers.queue_threshold_kb', 5120);
        $sizeInBytes = (int) ($file->getSize() ?? 0);

        if ($sizeInBytes <= 0) {
            $realPath = $file->getRealPath();
            if (is_string($realPath) && $realPath !== '' && is_file($realPath)) {
                $sizeInBytes = (int) (filesize($realPath) ?: 0);
            }
        }

        if ($sizeInBytes <= 0) {
            return true;
        }

        $sizeInKilobytes = (int) ceil($sizeInBytes / 1024);

        return $sizeInKilobytes >= $queueThresholdKb;
    }

    /**
     * Define el ancho de columnas para la plantilla de importación.
     *
     * Estrategia:
     * - Base estándar según longitud del header (mínimo/máximo)
     * - Overrides por columna para campos que suelen requerir más espacio
     *
     * @return array<string, float>
     */
    public function templateColumnWidths(): array
    {
        $headers = $this->requiredHeaders();
        $widths = [];
        $overrides = [
            'Nombre completo' => 32.0,
            'Correo electrónico' => 34.0,
            'Tipo de documento' => 22.0,
            'Número de documento' => 24.0,
            'Teléfono' => 18.0,
            'Dirección' => 34.0,
            'Complemento' => 26.0,
            'Barrio' => 22.0,
            'Ciudad' => 22.0,
            'Departamento' => 22.0,
            'Código postal' => 18.0,
            'País' => 20.0,
            'Referencias' => 28.0,
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $defaultWidth = $this->widthFromHeader($header);

            $widths[$column] = $overrides[$header] ?? $defaultWidth;
        }

        return $widths;
    }

    /**
     * Valida una fila usando las reglas de Store o Update según el cliente existente.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateRow(array $row, ?Customer $existingCustomer = null): array
    {
        $payload = CustomerNormalizer::normalize($row);
        $rules = $this->getValidationRules($existingCustomer);
        $messages = $this->getValidationMessages($existingCustomer);

        return Validator::make($payload, $rules, $messages)->validate();
    }

    /**
     * Busca el cliente usando document_number primero y, si no hay coincidencia, email.
     */
    public function findExistingCustomer(array $row): ?Customer
    {
        $payload = CustomerNormalizer::normalize($row);

        if (! empty($payload['documentNumber'])) {
            $customer = Customer::where('document_number', $payload['documentNumber'])->first();
            if ($customer) {
                return $customer;
            }
        }

        if (! empty($payload['email'])) {
            return Customer::where('email', $payload['email'])->first();
        }

        return null;
    }

    /**
     * Busca múltiples clientes existentes en batch para un conjunto de filas.
     * Reduce N queries individuales a solo 2 queries (whereIn por document_number y email).
     *
     * @param  array  $rows  Array de filas del Excel
     * @return array Map de filas a clientes existentes [row_index => Customer|null]
     */
    public function findExistingCustomersInBatch(array $rows): array
    {
        $documentNumbers = [];
        $emails = [];
        $normalizedRows = [];

        // Normalizar todas las filas y recolectar document_numbers y emails
        foreach ($rows as $index => $row) {
            $payload = CustomerNormalizer::normalize($row);
            $normalizedRows[$index] = $payload;

            if (! empty($payload['documentNumber'])) {
                $documentNumbers[$index] = $payload['documentNumber'];
            }
            if (! empty($payload['email'])) {
                $emails[$index] = $payload['email'];
            }
        }

        // Cargar todos los clientes por document_number (2 queries en total para todo el chunk)
        $customersByDocument = [];
        if (! empty($documentNumbers)) {
            Customer::whereIn('document_number', array_values($documentNumbers))
                ->get()
                ->each(function (Customer $customer) use (&$customersByDocument) {
                    $customersByDocument[$customer->document_number] = $customer;
                });
        }

        $customersByEmail = [];
        if (! empty($emails)) {
            Customer::whereIn('email', array_values($emails))
                ->get()
                ->each(function (Customer $customer) use (&$customersByEmail) {
                    $customersByEmail[$customer->email] = $customer;
                });
        }

        // Mapear cada fila a su cliente existente (prioridad: document_number > email)
        $result = [];
        foreach ($rows as $index => $row) {
            $payload = $normalizedRows[$index];
            $customer = null;

            // Primero buscar por document_number
            if (! empty($payload['documentNumber']) && isset($customersByDocument[$payload['documentNumber']])) {
                $customer = $customersByDocument[$payload['documentNumber']];
            }
            // Si no hay match por document_number, buscar por email
            elseif (! empty($payload['email']) && isset($customersByEmail[$payload['email']])) {
                $customer = $customersByEmail[$payload['email']];
            }

            $result[$index] = $customer;
        }

        return $result;
    }

    /**
     * Obtiene las reglas de validación del FormRequest correspondiente.
     */
    protected function getValidationRules(?Customer $existingCustomer): array
    {
        $request = $existingCustomer
            ? $this->resolveFormRequest(UpdateCustomerRequest::class, $existingCustomer)
            : $this->resolveFormRequest(StoreCustomerRequest::class);

        return $this->removeUniqueValidationRules($request->rules());
    }

    /**
     * Obtiene los mensajes personalizados del FormRequest correspondiente.
     */
    protected function getValidationMessages(?Customer $existingCustomer): array
    {
        $request = $existingCustomer
            ? $this->resolveFormRequest(UpdateCustomerRequest::class, $existingCustomer)
            : $this->resolveFormRequest(StoreCustomerRequest::class);

        return $request->messages();
    }

    /**
     * Resuelve una instancia del FormRequest sin activar la auto-validación.
     * Se usa `new` en lugar de `app()` para evitar que ValidatesWhenResolved
     * valide automáticamente el request HTTP actual (que está vacío en Artisan).
     */
    protected function resolveFormRequest(string $class, ?Customer $customer = null): FormRequest
    {
        /** @var FormRequest $request */
        $request = new $class;

        if ($customer) {
            $request->setRouteResolver(fn () => $this->createRouteStub($customer));
        }

        return $request;
    }

    /**
     * Crea un stub de ruta para que el FormRequest pueda resolver "$this->route('customer')".
     */
    protected function createRouteStub(Customer $customer): object
    {
        return new class($customer)
        {
            public function __construct(protected Customer $customer) {}

            public function parameter(string $key, $default = null)
            {
                return $key === 'customer' ? $this->customer : $default;
            }

            public function parameters(): array
            {
                return ['customer' => $this->customer];
            }
        };
    }

    protected function removeUniqueValidationRules(array $rules): array
    {
        $optimizedRules = [];

        foreach ($rules as $attribute => $attributeRules) {
            $normalizedRules = is_array($attributeRules)
                ? $attributeRules
                : explode('|', (string) $attributeRules);

            $optimizedRules[$attribute] = array_values(array_filter(
                $normalizedRules,
                fn (mixed $rule): bool => ! $this->isUniqueValidationRule($rule)
            ));
        }

        return $optimizedRules;
    }

    protected function isUniqueValidationRule(mixed $rule): bool
    {
        if ($rule instanceof Unique) {
            return true;
        }

        if (is_string($rule)) {
            return str_starts_with($rule, 'unique:');
        }

        return false;
    }

    protected function widthFromHeader(string $header): float
    {
        $headerLength = mb_strlen($header, 'UTF-8');
        $width = $headerLength + 6;

        return (float) max(16, min(30, $width));
    }
}
