<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rating' => fake()->numberBetween(4, 5),
            'comment' => fake()->randomElement([
                'Konsultasi sangat membantu, psikolognya sabar dan mendengarkan dengan baik.',
                'Pelayanan memuaskan, suasana nyaman, sangat recommended!',
                'Terima kasih atas bantuannya, saya merasa jauh lebih baik sekarang.',
                'Psikolog sangat profesional dan berpengalaman. Recommended!',
                'Sangat membantu mengatasi kecemasan saya. Terima kasih banyak!',
                null,
            ]),
            'is_published' => fake()->boolean(90),
        ];
    }
}
