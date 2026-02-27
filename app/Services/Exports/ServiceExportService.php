<?php

namespace App\Services\Exports;

use App\Models\Service;

/**
 * Defines the XLS contract for service exports.
 *
 * XLS Contract:
 * - Headers: ID, Nombre, Descripción, Costo, Precio, Moneda, Unidad, Estado, Duración (min)
 * - Headers match the import contract so the exported file can be re-imported.
 * - Boolean is_active is exported as "Activo" / "Inactivo".
 * - Defaults: currency=COP, duration=0
 *
 * @codeCoverageIgnore
 */
class ServiceExportService
{
    /**
     * Column headers for the exported spreadsheet.
     *
     * @return string[]
     */
    public function exportHeaders(): array
    {
        return [
            trans('exports.services.headers.id'),
            trans('exports.services.headers.name'),
            trans('exports.services.headers.description'),
            trans('exports.services.headers.cost'),
            trans('exports.services.headers.price'),
            trans('exports.services.headers.currency'),
            trans('exports.services.headers.unit'),
            trans('exports.services.headers.status'),
            trans('exports.services.headers.duration'),
        ];
    }

    /**
     * Convert a Service model into a flat row array matching exportHeaders().
     *
     * @return array<int, mixed>
     */
    public function normalizeRow(Service $service): array
    {
        return [
            $service->catalog_item_id,
            $service->name ?? '',
            $service->description ?? '',
            $service->cost ?? 0,
            $service->price ?? 0,
            $service->currency ?? 'COP',
            $service->unit?->value ?? $service->unit ?? 'unit',
            $service->is_active
                ? trans('exports.common.status.active')
                : trans('exports.common.status.inactive'),
            $service->duration ?? 0,
        ];
    }
}
