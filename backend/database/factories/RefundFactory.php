<?php

namespace Database\Factories;

use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 50000, 300000),
            'reason' => fake()->randomElement([
                'Cancel oleh pasien H-3',
                'Psikolog tidak hadir',
                'Force majeure - sakit',
                'Kesalahan sistem',
                null,
            ]),
            'status' => fake()->randomElement(['pending', 'approved', 'completed', 'rejected']),
        ];
    }
}
