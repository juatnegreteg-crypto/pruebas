<?php

namespace App\Models;

use App\Observers\QuoteItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([QuoteItemObserver::class])]
class QuoteItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'quote_id',
        'itemable_type',
        'itemable_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'subtotal',
        'tax_total',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return MorphTo<Product|Service|Bundle>
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
