<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'jurisdiction',
        'rate',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
    ];

    /**
     * @return HasMany<CatalogItemTax>
     */
    public function catalogItemTaxes(): HasMany
    {
        return $this->hasMany(CatalogItemTax::class);
    }
}
