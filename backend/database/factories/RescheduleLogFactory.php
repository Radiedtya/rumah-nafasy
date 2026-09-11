<?php

namespace Database\Factories;

use App\Models\RescheduleLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RescheduleLog>
 */
class RescheduleLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'old_date' => now()->addDays(3)->format('Y-m-d'),
            'old_start_time' => '14:00',
            'old_end_time' => '15:00',
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_start_time' => '16:00',
            'new_end_time' => '17:00',
            'reason' => fake()->randomElement([
                'Jadwal bentrok',
                'Sakit',
                'Mendesak',
                null,
            ]),
            'rescheduled_by' => fake()->randomElement(['pasien', 'psikolog', 'admin']),
        ];
    }
}
