<?php

namespace App\Models;

use App\Models\Concerns\HasObservations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory;

    use HasObservations;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'plate',
        'vin',
        'make',
        'model',
        'year',
        'type',
        'color',
        'fuel_type',
        'transmission',
        'mileage',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeThatAreMatchingSearchTerm(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $q) use ($search) {
            $pattern = "%{$search}%";
            $operator = $q->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $q->where(function (Builder $subQuery) use ($pattern, $operator) {
                $subQuery
                    ->where('plate', $operator, $pattern)
                    ->orWhere('vin', $operator, $pattern)
                    ->orWhere('make', $operator, $pattern)
                    ->orWhere('model', $operator, $pattern);
            })->orWhereHas('customer', function (Builder $customerQuery) use ($pattern, $operator) {
                $customerQuery->where('full_name', $operator, $pattern)
                    ->orWhere('document_number', $operator, $pattern);
            });
        });
    }
}
