<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasObservations;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class TechnicianAppoiment extends Model
{
    use HasFactory;
    use HasObservations;

    protected $table = 'technician_appoiments';

    protected $fillable = [
        'starts_at',
        'ends_at',
        'technician_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'technician_id' => 'integer',
            'status' => AppointmentStatus::class,
        ];
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }

    public function scopeByStatus(Builder $query, AppointmentStatus|string $status): Builder
    {
        return $query->where('status', $this->resolveStatus($status)->value);
    }

    public function scopeByTechnician(Builder $query, int $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeThatAreScheduledBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query
            ->where('starts_at', '>=', $from)
            ->where('ends_at', '<=', $to);
    }

    public function scopeThatAreNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled->value);
    }

    public function scopeThatAreOverlappingWithSlot(
        Builder $query,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
    ): Builder {
        return $query
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }

    public function hasValidSlot(): bool
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return false;
        }

        return $this->starts_at->lt($this->ends_at);
    }

    private function resolveStatus(AppointmentStatus|string $status): AppointmentStatus
    {
        if ($status instanceof AppointmentStatus) {
            return $status;
        }

        return AppointmentStatus::tryFrom($status)
            ?? throw new InvalidArgumentException("Estado de cita inválido: {$status}");
    }
}
