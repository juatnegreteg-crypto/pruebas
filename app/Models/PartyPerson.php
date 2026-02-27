<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyPerson extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'first_name',
        'last_name',
        'full_name',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
