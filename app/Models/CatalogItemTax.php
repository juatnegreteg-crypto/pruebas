<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItemTax extends Model
{
    protected $fillable = [
        'catalog_item_id',
        'tax_id',
        'rate',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
