<?php

namespace Database\Factories;

use App\Models\PsikologProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PsikologProfile>
 */
class PsikologProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bio' => fake()->paragraph(3),
            'experience_years' => fake()->numberBetween(1, 20),
            'license_no' => 'SIP-' . fake()->numberBetween(10000, 99999),
            'education' => fake()->randomElement([
                'S1 Psikologi, Universitas Indonesia',
                'S2 Psikologi Klinis, Universitas Gadjah Mada',
                'S1 Psikologi, Institut Teknologi Bandung',
                'S2 Psikologi Profesi, Universitas Padjadjaran',
                'S3 Psikologi, Universitas Airlangga',
            ]),
            'workplace' => fake()->randomElement([
                'Praktik Pribadi',
                'RS Premier Jakarta',
                'Klinik Mental Sehat',
                'Yayasan Peduli Hati',
                'Praktik Bersama',
            ]),
            'status' => fake()->randomElement([
                'verified', 'verified', 'verified', 'pending', 'suspended',
            ]),
            'is_available' => fake()->boolean(80),
            'rating_avg' => fake()->randomFloat(2, 3.5, 5.0),
            'total_reviews' => fake()->numberBetween(0, 100),
            'total_consultations' => fake()->numberBetween(0, 500),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_at' => now(),
            'is_available' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_at' => null,
            'is_available' => false,
        ]);
    }
}
