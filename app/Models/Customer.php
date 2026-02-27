<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Concerns\HasObservations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    use HasObservations;

    protected $fillable = [
        'party_id',
        'full_name',
        'email',
        'document_type',
        'document_number',
        'phone_number',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function quotes(): HasManyThrough
    {
        return $this->hasManyThrough(Quote::class, Vehicle::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function scopeThatAreMatchingSearchTerm(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $q) use ($search) {
            $pattern = "%{$search}%";
            $operator = $q->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where(function (Builder $subQuery) use ($pattern, $operator) {
                $subQuery->where('full_name', $operator, $pattern)
                    ->orWhere('email', $operator, $pattern)
                    ->orWhere('document_number', $operator, $pattern)
                    ->orWhereHas('party', function (Builder $partyQuery) use ($pattern, $operator): void {
                        $partyQuery->where('display_name', $operator, $pattern)
                            ->orWhereHas('emails', function (Builder $emailQuery) use ($pattern, $operator): void {
                                $emailQuery->where('email', $operator, $pattern);
                            });
                    });
            });
        });
    }

    public function scopeOrderedBy(Builder $query, ?string $sort, ?string $direction): Builder
    {
        return $query->orderBy($sort ?? 'created_at', $direction ?? 'desc');
    }
}
