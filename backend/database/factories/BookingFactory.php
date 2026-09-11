<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('+1 day', '+30 days');
        $startHour = fake()->randomElement([9, 10, 13, 14, 15, 16, 19, 20]);
        $duration = fake()->randomElement([30, 60, 90]);

        $startTime = sprintf('%02d:00', $startHour);
        $endTime = sprintf('%02d:%02d', $startHour + floor($duration / 60), ($duration % 60) * (60 / 60));

        if ($duration % 60 === 0) {
            $endTime = sprintf('%02d:00', $startHour + ($duration / 60));
        } else {
            $endTime = sprintf('%02d:30', $startHour + floor($duration / 60));
        }

        return [
            'booking_date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_id' => 'room-' . fake()->uuid(),
            'status' => fake()->randomElement(['confirmed', 'in_progress', 'completed']),
            'locked_until' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'booking_date' => now()->subDays(7)->format('Y-m-d'),
        ]);
    }
}
