<?php

namespace Database\Factories;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->randomElement(['09:00', '10:00', '13:00', '14:00', '15:00', '16:00', '19:00', '20:00']);
        $startHour = (int) substr($start, 0, 2);
        $endHour = $startHour + fake()->numberBetween(2, 4);

        return [
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => $start,
            'end_time' => sprintf('%02d:00', min($endHour, 22)),
            'is_available' => fake()->boolean(90),
        ];
    }
}
