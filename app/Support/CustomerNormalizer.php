<?php

namespace App\Support;

/**
 * Extrae y normaliza los datos de una fila del Excel de importación de clientes.
 *
 * Esta clase solo extrae los campos y convierte valores vacíos a null.
 * Las transformaciones adicionales (trim, lowercase, etc.) las realizan
 * los FormRequest (StoreCustomerRequest, UpdateCustomerRequest) en prepareForValidation().
 */
class CustomerNormalizer
{
    /**
     * Alias soportados por columna (después de WithHeadingRow/slug).
     */
    private const COLUMN_ALIASES = [
        'fullName' => ['full_name', 'nombre_completo', 'nombre completo'],
        'email' => ['email', 'correo_electronico', 'correo electrónico', 'correo electronico'],
        'documentType' => ['document_type', 'tipo_de_documento', 'tipo de documento'],
        'documentNumber' => ['document_number', 'numero_de_documento', 'número de documento', 'numero de documento'],
        'phoneNumber' => ['phone_number', 'telefono', 'teléfono'],
        'street' => ['street', 'direccion', 'dirección'],
        'complement' => ['complement', 'complemento'],
        'neighborhood' => ['neighborhood', 'barrio'],
        'city' => ['city', 'ciudad'],
        'state' => ['state', 'departamento'],
        'postalCode' => ['postal_code', 'codigo_postal', 'código postal', 'codigo postal'],
        'country' => ['country', 'pais', 'país'],
        'reference' => ['reference', 'referencias'],
    ];

    /**
     * Normaliza una fila del Excel al formato esperado por la aplicación.
     *
     * @param  array  $row  Fila del Excel con columnas técnicas o amigables de plantilla
     * @return array Array con los campos extraídos (convierte strings vacíos a null)
     */
    public static function normalize(array $row): array
    {
        $street = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['street']));
        $complement = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['complement']));
        $neighborhood = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['neighborhood']));
        $city = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['city']));
        $state = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['state']));
        $postalCode = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['postalCode']));
        $country = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['country']));
        $reference = self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['reference']));

        $payload = [
            'fullName' => self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['fullName'])),
            'email' => self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['email'])),
            'documentType' => self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['documentType'])),
            'documentNumber' => self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['documentNumber'])),
            'phoneNumber' => self::emptyToNull(self::valueFromAliases($row, self::COLUMN_ALIASES['phoneNumber'])),
        ];

        $hasAddress = collect([
            $street,
            $complement,
            $neighborhood,
            $city,
            $state,
            $postalCode,
            $country,
            $reference,
        ])->filter()->isNotEmpty();

        if ($hasAddress) {
            $payload['addresses'] = [[
                'type' => 'primary',
                'isPrimary' => true,
                'street' => $street,
                'complement' => $complement,
                'neighborhood' => $neighborhood,
                'city' => $city,
                'state' => $state,
                'postalCode' => $postalCode,
                'country' => $country ?? 'Colombia',
                'reference' => $reference,
            ]];
        }

        return $payload;
    }

    protected static function valueFromAliases(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }

        return null;
    }

    /**
     * Convierte strings vacíos a null sin realizar transformaciones adicionales.
     * El prepareForValidation() de los FormRequest se encargará del trim/lowercase.
     */
    protected static function emptyToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
