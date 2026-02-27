<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            ['day_of_week' => 0, 'is_working_day' => true, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_duration' => 30],
            ['day_of_week' => 1, 'is_working_day' => true, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_duration' => 30],
            ['day_of_week' => 2, 'is_working_day' => true, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_duration' => 30],
            ['day_of_week' => 3, 'is_working_day' => true, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_duration' => 30],
            ['day_of_week' => 4, 'is_working_day' => true, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_duration' => 30],
            ['day_of_week' => 5, 'is_working_day' => false, 'start_time' => null, 'end_time' => null, 'slot_duration' => null],
            ['day_of_week' => 6, 'is_working_day' => false, 'start_time' => null, 'end_time' => null, 'slot_duration' => null],
        ];

        foreach ($days as $day) {
            Schedule::query()->updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                $day,
            );
        }
    }
}
