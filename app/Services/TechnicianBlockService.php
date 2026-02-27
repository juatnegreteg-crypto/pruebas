<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\TechnicianAppoiment;
use App\Models\TechnicianBlock;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TechnicianBlockService
{
    public function createBlock(Technician $tech, array $data): TechnicianBlock
    {
        $overlapping = $this->findOverlappingBlocks($tech, $data);

        if ($overlapping->isNotEmpty()) {
            $first = $overlapping->first();

            $exception = ValidationException::withMessages([
                'dates' => 'El bloqueo se solapa con otro existente.',
            ]);

            $exception->response = response()->json([
                'message' => 'El bloqueo se solapa con otro existente.',
                'overlapping_block' => [
                    'id' => $first->id,
                    'start_date' => $first->start_date->toDateString(),
                    'end_date' => $first->end_date->toDateString(),
                    'reason' => $first->reason,
                ],
            ], 422);

            throw $exception;
        }

        $conflicts = $this->detectAppointmentConflicts($tech, $data);

        if ($conflicts->isNotEmpty()) {
            $message = "No se puede crear el bloqueo: existen {$conflicts->count()} cita(s) agendadas en ese periodo.";

            $exception = ValidationException::withMessages([
                'appointments' => $message,
            ]);

            $exception->response = response()->json([
                'message' => $message,
                'conflicts' => $conflicts->map(fn (TechnicianAppoiment $a) => [
                    'id' => $a->id,
                    'date' => $a->starts_at->toDateString(),
                    'time' => $a->starts_at->format('H:i'),
                    'status' => $a->status->value,
                ])->all(),
                'action' => 'Cancele o reagende las citas antes de registrar el bloqueo.',
            ], 422);

            throw $exception;
        }

        return $tech->blocks()->create($data);
    }

    public function deleteBlock(TechnicianBlock $block): void
    {
        $block->delete();
    }

    public function getBlocks(Technician $tech, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return $tech->blocks()
            ->when($from && $to, fn ($q) => $q->thatAreBetweenDates($from, $to))
            ->orderBy('start_date')
            ->get();
    }

    public function detectAppointmentConflicts(Technician $tech, array $blockData): Collection
    {
        $startDate = Carbon::parse($blockData['start_date']);
        $endDate = Carbon::parse($blockData['end_date']);
        $isFullDay = $blockData['is_full_day'] ?? true;

        if ($isFullDay) {
            return TechnicianAppoiment::byTechnician($tech->id)
                ->thatAreNotCancelled()
                ->thatAreOverlappingWithSlot($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay())
                ->get();
        }

        $conflicts = collect();

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $from = $date->copy()->setTimeFromTimeString($blockData['start_time']);
            $to = $date->copy()->setTimeFromTimeString($blockData['end_time']);

            $dayConflicts = TechnicianAppoiment::byTechnician($tech->id)
                ->thatAreNotCancelled()
                ->thatAreOverlappingWithSlot($from, $to)
                ->get();

            $conflicts = $conflicts->merge($dayConflicts);
        }

        return $conflicts->unique('id')->values();
    }

    public function isBlockedAt(Technician $tech, CarbonInterface $date, ?string $time = null): bool
    {
        $blocks = $tech->blocks()->byDate($date)->get();

        if ($blocks->isEmpty()) {
            return false;
        }

        if ($time === null) {
            return true;
        }

        return $blocks->contains(fn (TechnicianBlock $block) => $block->coversTime($time));
    }

    public function getBlockedSlotsForDate(Technician $tech, CarbonInterface $date): array
    {
        return $tech->blocks()
            ->byDate($date)
            ->get()
            ->map(function (TechnicianBlock $block) {
                if ($block->is_full_day) {
                    return ['start_time' => null, 'end_time' => null, 'is_full_day' => true];
                }

                return [
                    'start_time' => substr($block->start_time, 0, 5),
                    'end_time' => substr($block->end_time, 0, 5),
                    'is_full_day' => false,
                ];
            })
            ->all();
    }

    private function findOverlappingBlocks(Technician $tech, array $data): Collection
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $blocks = $tech->blocks()->thatAreBetweenDates($startDate, $endDate)->get();

        if ($data['is_full_day'] ?? true) {
            return $blocks;
        }

        $start = substr($data['start_time'], 0, 5);
        $end = substr($data['end_time'], 0, 5);

        return $blocks->filter(function (TechnicianBlock $block) use ($start, $end) {
            if ($block->is_full_day) {
                return true;
            }

            $blockStart = substr($block->start_time, 0, 5);
            $blockEnd = substr($block->end_time, 0, 5);

            return $start < $blockEnd && $end > $blockStart;
        })->values();
    }
}
