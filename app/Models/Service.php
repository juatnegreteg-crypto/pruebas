<?php

namespace App\Models;

use App\Models\Concerns\CatalogItemAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use CatalogItemAttributes;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'service_details';

    protected $primaryKey = 'catalog_item_id';

    public $incrementing = false;

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
        'duration',
    ];

    protected $casts = [
        'duration' => 'integer',
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function bundles(): MorphToMany
    {
        return $this->morphToMany(Bundle::class, 'bundleable', 'bundle_items', 'bundleable_id', 'bundle_id')
            ->using(BundleItem::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function quoteItems(): MorphMany
    {
        return $this->morphMany(QuoteItem::class, 'itemable');
    }
}
