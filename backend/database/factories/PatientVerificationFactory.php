<?php

namespace Database\Factories;

use App\Models\PatientVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientVerification>
 */
class PatientVerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_type' => fake()->randomElement(['ktp', 'sim', 'ktm', 'kartu_pelajar']),
            'id_number' => fake()->numerify('################'),
            'status' => fake()->randomElement(['pending', 'verified', 'verified', 'rejected']),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }
}
