<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianBlock extends Model
{
    protected $fillable = [
        'technician_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_full_day' => 'boolean',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function scopeThatAreBetweenDates(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->where('start_date', '<=', $to->copy()->endOfDay())
            ->where('end_date', '>=', $from->copy()->startOfDay());
    }

    public function scopeByDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where('start_date', '<=', $date->copy()->endOfDay())
            ->where('end_date', '>=', $date->copy()->startOfDay());
    }

    public function coversTime(string $time): bool
    {
        if ($this->is_full_day) {
            return true;
        }

        $normalized = substr($time, 0, 5);
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);

        return $normalized >= $start && $normalized < $end;
    }
}
