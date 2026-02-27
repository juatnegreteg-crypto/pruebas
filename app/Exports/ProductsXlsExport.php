<?php

namespace App\Exports;

use App\Services\Exports\ProductExportQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsXlsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    protected string $locale;

    public function __construct(array $filters = [], ?string $locale = null)
    {
        $this->filters = $filters;
        $this->locale = $locale ?? app()->getLocale();
    }

    public function query()
    {
        return (new ProductExportQuery)
            ->build($this->filters);
    }

    public function headings(): array
    {
        app()->setLocale($this->locale);

        return [
            trans('exports.products.headers.id'),
            trans('exports.products.headers.sku'),
            trans('exports.products.headers.name'),
            trans('exports.products.headers.description'),
            trans('exports.products.headers.cost'),
            trans('exports.products.headers.price'),
            trans('exports.products.headers.currency'),
            trans('exports.products.headers.unit'),
            trans('exports.products.headers.status'),
            trans('exports.products.headers.stock'),
            trans('exports.products.headers.created_at'),
        ];
    }

    public function map($product): array
    {
        app()->setLocale($this->locale);

        return [
            $product->catalog_item_id,
            $product->sku,
            $product->name,
            $product->description ?? '',
            number_format($product->cost, 2, '.', ''),
            number_format($product->price, 2, '.', ''),
            $product->currency,
            $product->unit?->value ?? $product->unit,
            $product->is_active
                ? trans('exports.common.status.active')
                : trans('exports.common.status.inactive'),
            $product->stock,
            $product->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
