<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'RMF-' . now()->format('ymd') . '-' . strtoupper(fake()->bothify('??????')),
            'calculated_price' => fake()->randomFloat(2, 50000, 300000),
            'consultation_type' => fake()->randomElement(['video', 'chat']),
            'status' => fake()->randomElement([
                'pending_payment', 'paid', 'scheduled', 'completed',
            ]),
            'expires_at' => now()->addDay(),
        ];
    }

    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_payment',
            'expires_at' => now()->addDay(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'scheduled_at' => now()->subDays(7),
        ]);
    }
}
