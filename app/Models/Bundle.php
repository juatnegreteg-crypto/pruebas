<?php

namespace App\Models;

use App\Models\Concerns\CatalogItemAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Bundle extends Model
{
    use CatalogItemAttributes;
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'bundle_details';

    protected $primaryKey = 'catalog_item_id';

    protected $keyType = 'int';

    protected $fillable = [
        'catalog_item_id',
        'name',
        'description',
        'cost',
        'price',
        'currency',
        'unit',
        'is_active',
        'discount_strategy',
        'items_count',
    ];

    protected $casts = [
        'items_count' => 'integer',
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * Pivot items for the bundleable morph; used by bundles(), products(), and services().
     *
     * @return HasMany<BundleItem>
     */
    public function bundleables(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id', 'catalog_item_id');
    }

    public function bundles(): MorphToMany
    {
        return $this->morphedByMany(self::class, 'bundleable', 'bundle_items', 'bundle_id', 'bundleable_id')
            ->using(BundleItem::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'bundleable', 'bundle_items', 'bundle_id', 'bundleable_id')
            ->using(BundleItem::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'bundleable', 'bundle_items', 'bundle_id', 'bundleable_id')
            ->using(BundleItem::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function items(): Collection
    {
        return $this->bundleables->pluck('bundleable')
            ->filter()
            ->values();
    }

    public function quoteItems(): MorphMany
    {
        return $this->morphMany(QuoteItem::class, 'itemable');
    }
}
