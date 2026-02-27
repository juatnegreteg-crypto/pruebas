<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianAvailability extends Model
{
    protected $fillable = [
        'technician_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => DayOfWeek::class,
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
