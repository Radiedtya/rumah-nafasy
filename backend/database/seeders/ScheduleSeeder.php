<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $psikologs = User::role('psikolog')->get();

        // Template jadwal: [day, start, end]
        $templates = [
            [1, '09:00', '12:00'],  // Senin pagi
            [1, '14:00', '17:00'],  // Senin sore
            [2, '10:00', '13:00'],  // Selasa
            [3, '14:00', '18:00'],  // Rabu sore
            [4, '09:00', '12:00'],  // Kamis pagi
            [5, '15:00', '20:00'],  // Jumat sore-malam
            [6, '10:00', '14:00'],  // Sabtu
        ];

        foreach ($psikologs as $psikolog) {
            // Setiap psikolog punya 3-5 jadwal per minggu
            $selectedTemplates = collect($templates)->random(fake()->numberBetween(3, 5));

            foreach ($selectedTemplates as $template) {
                Schedule::firstOrCreate([
                    'psikolog_id' => $psikolog->id,
                    'day_of_week' => $template[0],
                    'start_time' => $template[1],
                    'end_time' => $template[2],
                ], [
                    'is_available' => true,
                ]);
            }
        }
    }
}