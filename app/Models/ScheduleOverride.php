<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduleOverride extends Model
{
    protected $fillable = [
        'date',
        'is_working_day',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_working_day' => 'boolean',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    public function scopeThatAreHolidays(Builder $query): Builder
    {
        return $query->where('is_working_day', false);
    }

    public function scopeThatAreBetweenDates(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }
}
