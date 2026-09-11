<?php

namespace Database\Factories;

use App\Models\ConsultationNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationNote>
 */
class ConsultationNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraphs(3, true),
        ];
    }
}
