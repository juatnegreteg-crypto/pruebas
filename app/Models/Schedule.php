<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'day_of_week',
        'is_working_day',
        'start_time',
        'end_time',
        'slot_duration',
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    public function scopeThatAreWorkingDays(Builder $query): Builder
    {
        return $query->where('is_working_day', true);
    }
}
