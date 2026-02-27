<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleOverride;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleService
{
    public function saveWeeklySchedule(array $days): void
    {
        DB::transaction(function () use ($days) {
            foreach ($days as $day) {
                if (
                    ($day['is_working_day'] ?? false)
                    && isset($day['start_time'], $day['end_time'])
                    && $day['start_time'] >= $day['end_time']
                ) {
                    throw new InvalidArgumentException(
                        "La hora de inicio debe ser menor que la hora de fin para el día {$day['day_of_week']}."
                    );
                }

                Schedule::updateOrCreate(
                    ['day_of_week' => $day['day_of_week']],
                    [
                        'is_working_day' => $day['is_working_day'] ?? false,
                        'start_time' => $day['start_time'] ?? null,
                        'end_time' => $day['end_time'] ?? null,
                        'slot_duration' => $day['slot_duration'] ?? null,
                    ]
                );
            }
        });
    }

    public function getWeeklySchedule(): Collection
    {
        return Schedule::orderBy('day_of_week')->get();
    }

    public function addOverride(array $data): ScheduleOverride
    {
        return DB::transaction(fn () => ScheduleOverride::create($data));
    }

    public function removeOverride(ScheduleOverride $override): void
    {
        DB::transaction(fn () => $override->delete());
    }

    public function getOverrides(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return ScheduleOverride::thatAreBetweenDates($from, $to)
            ->orderBy('date')
            ->get();
    }

    public function getAllOverrides(): Collection
    {
        return ScheduleOverride::orderBy('date')->get();
    }

    public function getAvailableSlots(CarbonInterface $from, CarbonInterface $to): array
    {
        $schedules = Schedule::thatAreWorkingDays()
            ->get()
            ->keyBy('day_of_week');

        $overrides = ScheduleOverride::thatAreBetweenDates($from, $to)
            ->get()
            ->keyBy(fn (ScheduleOverride $o) => $o->date->toDateString());

        $slots = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dateKey = $date->toDateString();

            // (1) Override takes priority.
            if ($override = $overrides->get($dateKey)) {
                if (! $override->is_working_day) {
                    continue;
                }

                $slots[$dateKey] = $this->generateSlots(
                    $date,
                    $override->start_time->format('H:i:s'),
                    $override->end_time->format('H:i:s'),
                    $schedules->get($date->dayOfWeekIso - 1)?->slot_duration ?? 30,
                );

                continue;
            }

            // (2) Weekly schedule.
            $dayIndex = $date->dayOfWeekIso - 1; // Monday=0 … Sunday=6
            $schedule = $schedules->get($dayIndex);

            if (! $schedule) {
                continue;
            }

            $slots[$dateKey] = $this->generateSlots(
                $date,
                $schedule->start_time->format('H:i:s'),
                $schedule->end_time->format('H:i:s'),
                $schedule->slot_duration,
            );
        }

        return $slots;
    }

    public function isConfigured(): bool
    {
        return Schedule::thatAreWorkingDays()->exists();
    }

    public function containsSlot(CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        if ($startsAt->gte($endsAt)) {
            return false;
        }

        if (! $startsAt->isSameDay($endsAt)) {
            return false;
        }

        $date = $startsAt->copy()->startOfDay();
        $dayKey = $date->toDateString();
        $slots = $this->getAvailableSlots($date, $date)[$dayKey] ?? [];

        $expectedStart = $startsAt->toDateTimeString();
        $expectedEnd = $endsAt->toDateTimeString();

        foreach ($slots as $slot) {
            if (
                $slot['start'] === $expectedStart
                && $slot['end'] === $expectedEnd
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    public function generateSlots(CarbonInterface $date, string $startTime, string $endTime, int $duration): array
    {
        $start = $date->copy()->setTimeFromTimeString($startTime);
        $end = $date->copy()->setTimeFromTimeString($endTime);
        $slots = [];

        while ($start->copy()->addMinutes($duration)->lte($end)) {
            $slotEnd = $start->copy()->addMinutes($duration);

            $slots[] = [
                'start' => $start->toDateTimeString(),
                'end' => $slotEnd->toDateTimeString(),
            ];

            $start = $slotEnd;
        }

        return $slots;
    }
}
