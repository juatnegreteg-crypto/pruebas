<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartyAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'party_id',
        'type',
        'is_primary',
        'street',
        'complement',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
