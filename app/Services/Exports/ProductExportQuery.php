<?php

namespace App\Services\Exports;

use App\Models\Product;

class ProductExportQuery
{
    public function build(array $filters = [])
    {
        $query = Product::query()
            ->select([
                'product_details.catalog_item_id',
                'product_details.sku',
                'catalog_items.name',
                'catalog_items.description',
                'catalog_items.cost',
                'catalog_items.price',
                'catalog_items.currency',
                'catalog_items.unit',
                'catalog_items.is_active',
                'product_details.stock',
                'product_details.created_at',
            ])
            ->leftJoin('catalog_items', 'catalog_items.id', '=', 'product_details.catalog_item_id')
            ->orderBy('product_details.catalog_item_id');

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['price_from'])) {
            $query->where('price', '>=', $filters['price_from']);
        }

        if (! empty($filters['price_to'])) {
            $query->where('price', '<=', $filters['price_to']);
        }

        return $query;
    }
}
