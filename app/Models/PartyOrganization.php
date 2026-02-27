<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyOrganization extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'legal_name',
        'trade_name',
        'tax_id',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
