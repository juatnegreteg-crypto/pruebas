<?php

namespace App\Models;

use App\Enums\CatalogItemType;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Product|Service|Bundle $bundleable
 */
class BundleItem extends MorphPivot
{
    protected $table = 'bundle_items';

    public $incrementing = false;

    protected $fillable = [
        'bundle_id',
        'bundleable_id',
        'bundleable_type',
        'quantity',
    ];

    /**
     * @return MorphTo<Product|Service|Bundle>
     */
    public function bundleable(): MorphTo
    {
        return $this->morphTo('bundleable');
    }

    public function itemTypeEnum(): ?CatalogItemType
    {
        return CatalogItemType::tryFrom($this->bundleable_type);
    }
}
