<?php

namespace App\Models;

use App\Enums\CatalogItemType;
use App\Enums\UnitOfMeasure;
use App\Models\Concerns\HasObservations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogItem extends Model
{
    use HasObservations;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'description',
        'cost',
        'price',
        'currency',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'type' => CatalogItemType::class,
        'cost' => 'decimal:4',
        'price' => 'decimal:4',
        'is_active' => 'boolean',
        'unit' => UnitOfMeasure::class,
    ];

    /**
     * @return HasOne<Product>
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'catalog_item_id');
    }

    /**
     * @return HasOne<Service>
     */
    public function service(): HasOne
    {
        return $this->hasOne(Service::class, 'catalog_item_id');
    }

    /**
     * @return HasOne<Bundle>
     */
    public function bundle(): HasOne
    {
        return $this->hasOne(Bundle::class, 'catalog_item_id');
    }

    /**
     * @return HasMany<CatalogItemTax>
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(CatalogItemTax::class, 'catalog_item_id');
    }

    public function item(): Attribute
    {
        return Attribute::get(function (): Product|Service|Bundle|null {
            $item = match ($this->type) {
                CatalogItemType::PRODUCT => $this->product,
                CatalogItemType::SERVICE => $this->service,
                CatalogItemType::BUNDLE => $this->bundle,
                default => null,
            };
            $item?->setRelation('catalogItem', $this);

            return $item;
        });
    }
}
