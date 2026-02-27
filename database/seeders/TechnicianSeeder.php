<?php

namespace Database\Seeders;

use App\Models\Technician;
use App\Models\TechnicianAvailability;
use App\Models\TechnicianBlock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TechnicianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('technicians')) {
            return;
        }

        $technicians = [
            [
                'name' => 'Juan Camilo Rojas',
                'email' => 'juan.rojas@demo.cda.local',
                'phone' => '3001000101',
                'is_active' => true,
                'availabilities' => [
                    ['day_of_week' => 0, 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '12:00'],
                ],
                'blocks' => [
                    ['start_date' => '2026-03-20', 'end_date' => '2026-03-20', 'is_full_day' => true, 'reason' => 'Cita médica'],
                    ['start_date' => '2026-04-06', 'end_date' => '2026-04-10', 'is_full_day' => true, 'reason' => 'Vacaciones Semana Santa'],
                ],
            ],
            [
                'name' => 'Maria Fernanda Lopez',
                'email' => 'maria.lopez@demo.cda.local',
                'phone' => '3001000102',
                'is_active' => true,
                'availabilities' => [
                    ['day_of_week' => 0, 'start_time' => '12:00', 'end_time' => '17:00'],
                    ['day_of_week' => 1, 'start_time' => '12:00', 'end_time' => '17:00'],
                    ['day_of_week' => 2, 'start_time' => '12:00', 'end_time' => '17:00'],
                    ['day_of_week' => 3, 'start_time' => '12:00', 'end_time' => '17:00'],
                    ['day_of_week' => 4, 'start_time' => '12:00', 'end_time' => '17:00'],
                ],
                'blocks' => [
                    ['start_date' => '2026-03-25', 'end_date' => '2026-03-25', 'is_full_day' => false, 'start_time' => '14:00', 'end_time' => '17:00', 'reason' => 'Trámite personal'],
                ],
            ],
            [
                'name' => 'Diego Alonso Pineda',
                'email' => 'diego.pineda@demo.cda.local',
                'phone' => '3001000103',
                'is_active' => true,
                'availabilities' => [
                    ['day_of_week' => 0, 'start_time' => '09:00', 'end_time' => '16:00'],
                    ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '16:00'],
                    ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '16:00'],
                ],
                'blocks' => [
                    ['start_date' => '2026-04-01', 'end_date' => '2026-04-01', 'is_full_day' => false, 'start_time' => '09:00', 'end_time' => '11:00', 'reason' => 'Capacitación técnica'],
                    ['start_date' => '2026-05-01', 'end_date' => '2026-05-01', 'is_full_day' => true, 'reason' => 'Día del Trabajo'],
                ],
            ],
            [
                'name' => 'Sofia Andrea Ramirez',
                'email' => 'sofia.ramirez@demo.cda.local',
                'phone' => '3001000104',
                'is_active' => false,
                'availabilities' => [],
                'blocks' => [],
            ],
        ];

        foreach ($technicians as $index => $seed) {
            $technician = Technician::query()->updateOrCreate(
                ['id' => $index + 1],
                [
                    'name' => $seed['name'],
                    'email' => $seed['email'],
                    'phone' => $seed['phone'],
                    'is_active' => $seed['is_active'],
                ],
            );

            if (! Schema::hasTable('technician_availabilities')) {
                continue;
            }

            TechnicianAvailability::query()
                ->where('technician_id', $technician->id)
                ->delete();

            if (! empty($seed['availabilities'])) {
                $rows = array_map(
                    static fn (array $availability): array => [
                        'technician_id' => $technician->id,
                        'day_of_week' => $availability['day_of_week'],
                        'start_time' => $availability['start_time'],
                        'end_time' => $availability['end_time'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    $seed['availabilities'],
                );

                TechnicianAvailability::query()->insert($rows);
            }

            TechnicianBlock::query()
                ->where('technician_id', $technician->id)
                ->delete();

            if (! empty($seed['blocks'])) {
                $blockRows = array_map(
                    static fn (array $block): array => [
                        'technician_id' => $technician->id,
                        'start_date' => $block['start_date'],
                        'end_date' => $block['end_date'],
                        'is_full_day' => $block['is_full_day'],
                        'start_time' => $block['start_time'] ?? null,
                        'end_time' => $block['end_time'] ?? null,
                        'reason' => $block['reason'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    $seed['blocks'],
                );

                TechnicianBlock::query()->insert($blockRows);
            }
        }
    }
}
