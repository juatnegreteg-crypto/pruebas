<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Models\Technician;
use App\Models\TechnicianAvailability;
use App\Models\TechnicianBlock;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TechnicianAvailabilityService
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
    ) {}

    public function saveAvailability(Technician $tech, array $days): void
    {
        $cdaSchedules = Schedule::all()->keyBy('day_of_week');

        $toUpsert = [];
        $activeDays = [];

        foreach ($days as $day) {
            if (! ($day['is_available'] ?? false)) {
                continue;
            }

            $dayOfWeek = $day['day_of_week'];
            $start = $day['start_time'];
            $end = $day['end_time'];
            $schedule = $cdaSchedules->get($dayOfWeek);

            if (! $this->isWithinCdaSchedule($schedule, $start, $end)) {
                if (! $schedule || ! $schedule->is_working_day) {
                    throw ValidationException::withMessages([
                        'days' => "El día {$dayOfWeek} no es laborable en el CDA.",
                    ]);
                }

                $cdaStart = $schedule->start_time->format('H:i');
                $cdaEnd = $schedule->end_time->format('H:i');

                throw ValidationException::withMessages([
                    'days' => "El horario del técnico para {$dayOfWeek} debe estar dentro del horario del CDA ({$cdaStart} – {$cdaEnd}).",
                ]);
            }

            $toUpsert[] = [
                'technician_id' => $tech->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $start,
                'end_time' => $end,
            ];
            $activeDays[] = $dayOfWeek;
        }

        DB::transaction(function () use ($tech, $toUpsert, $activeDays) {
            if ($toUpsert) {
                TechnicianAvailability::upsert(
                    $toUpsert,
                    ['technician_id', 'day_of_week'],
                    ['start_time', 'end_time'],
                );
            }

            $tech->availabilities()
                ->whereNotIn('day_of_week', $activeDays)
                ->delete();
        });
    }

    public function getAvailability(Technician $tech): Collection
    {
        $availabilities = $tech->availabilities()->get()->keyBy(
            fn (TechnicianAvailability $a) => $a->day_of_week->value
        );

        return collect(DayOfWeek::cases())->map(function (DayOfWeek $day) use ($availabilities) {
            $availability = $availabilities->get($day->value);

            return [
                'day_of_week' => $day->value,
                'is_available' => $availability !== null,
                'start_time' => $availability?->start_time,
                'end_time' => $availability?->end_time,
            ];
        });
    }

    public function isWithinCdaSchedule(?Schedule $schedule, string $start, string $end): bool
    {
        if (! $schedule || ! $schedule->is_working_day) {
            return false;
        }

        $cdaStart = $schedule->start_time->format('H:i');
        $cdaEnd = $schedule->end_time->format('H:i');

        $normalizedStart = substr($start, 0, 5);
        $normalizedEnd = substr($end, 0, 5);

        return $normalizedStart >= $cdaStart && $normalizedEnd <= $cdaEnd;
    }

    public function hasAvailability(Technician $tech): bool
    {
        return $tech->availabilities()->exists();
    }

    public function getEffectiveAvailability(Technician $tech, CarbonInterface $from, CarbonInterface $to): array
    {
        $schedules = Schedule::all()->keyBy('day_of_week');

        $overrides = ScheduleOverride::thatAreBetweenDates($from, $to)
            ->get()
            ->keyBy(fn (ScheduleOverride $o) => $o->date->toDateString());

        $availabilities = $tech->availabilities()->get()->keyBy(
            fn (TechnicianAvailability $a) => $a->day_of_week->value
        );

        $blocks = $tech->blocks()->thatAreBetweenDates($from, $to)->get();

        $result = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dateKey = $date->toDateString();
            $dayIndex = $date->dayOfWeekIso - 1;

            // 1. CDA schedule for this date
            $cda = $this->resolveCdaForDate($dateKey, $dayIndex, $schedules, $overrides);

            if (! $cda) {
                continue;
            }

            // 2. Technician base availability
            $availability = $availabilities->get($dayIndex);

            if (! $availability) {
                continue;
            }

            // 3. Intersection: technician range ∩ CDA range
            $effectiveStart = max(substr($availability->start_time, 0, 5), $cda['start']);
            $effectiveEnd = min(substr($availability->end_time, 0, 5), $cda['end']);

            if ($effectiveStart >= $effectiveEnd) {
                continue;
            }

            // 4. Subtract blocks
            $ranges = $this->subtractBlocks(
                [['start' => $effectiveStart, 'end' => $effectiveEnd]],
                $blocks,
                $date,
            );

            if (! $ranges) {
                continue;
            }

            // 5. Generate slots from remaining ranges
            $slots = [];

            foreach ($ranges as $range) {
                $slots = array_merge(
                    $slots,
                    $this->scheduleService->generateSlots($date, $range['start'], $range['end'], $cda['slot_duration']),
                );
            }

            if ($slots) {
                $result[$dateKey] = $slots;
            }
        }

        return $result;
    }

    public function getActiveTechniciansWithStatus(): Collection
    {
        return Technician::thatAreActive()
            ->withCount('availabilities')
            ->get()
            ->map(function (Technician $tech) {
                $tech->has_availability = $tech->availabilities_count > 0;

                return $tech;
            });
    }

    /**
     * @return array{start: string, end: string, slot_duration: int}|null
     */
    private function resolveCdaForDate(string $dateKey, int $dayIndex, Collection $schedules, Collection $overrides): ?array
    {
        $override = $overrides->get($dateKey);

        if ($override) {
            if (! $override->is_working_day) {
                return null;
            }

            return [
                'start' => $override->start_time->format('H:i'),
                'end' => $override->end_time->format('H:i'),
                'slot_duration' => $schedules->get($dayIndex)?->slot_duration ?? 30,
            ];
        }

        $schedule = $schedules->get($dayIndex);

        if (! $schedule || ! $schedule->is_working_day) {
            return null;
        }

        return [
            'start' => $schedule->start_time->format('H:i'),
            'end' => $schedule->end_time->format('H:i'),
            'slot_duration' => $schedule->slot_duration,
        ];
    }

    /**
     * @param  array<int, array{start: string, end: string}>  $ranges
     * @return array<int, array{start: string, end: string}>
     */
    private function subtractBlocks(array $ranges, Collection $blocks, CarbonInterface $date): array
    {
        $dayBlocks = $blocks->filter(
            fn (TechnicianBlock $b) => $b->start_date->lte($date) && $b->end_date->gte($date)
        );

        if ($dayBlocks->contains(fn (TechnicianBlock $b) => $b->is_full_day)) {
            return [];
        }

        foreach ($dayBlocks as $block) {
            $ranges = $this->subtractRange($ranges, substr($block->start_time, 0, 5), substr($block->end_time, 0, 5));
        }

        return $ranges;
    }

    /**
     * @param  array<int, array{start: string, end: string}>  $ranges
     * @return array<int, array{start: string, end: string}>
     */
    private function subtractRange(array $ranges, string $blockStart, string $blockEnd): array
    {
        $result = [];

        foreach ($ranges as $range) {
            if ($blockEnd <= $range['start'] || $blockStart >= $range['end']) {
                $result[] = $range;

                continue;
            }

            if ($blockStart > $range['start']) {
                $result[] = ['start' => $range['start'], 'end' => $blockStart];
            }

            if ($blockEnd < $range['end']) {
                $result[] = ['start' => $blockEnd, 'end' => $range['end']];
            }
        }

        return $result;
    }
}
