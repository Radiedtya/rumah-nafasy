<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'payment_channel' => fake()->randomElement([
                'bca_va', 'bni_va', 'bri_va', 'gopay', 'ovo', 'qris', 'credit_card',
            ]),
            'transaction_id' => strtoupper(fake()->bothify('TRX-########')),
            'status' => fake()->randomElement(['pending', 'success', 'success', 'failed']),
            'payment_url' => 'https://app.sandbox.midtrans.com/snap/v2/transaction/' . fake()->uuid(),
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'paid_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }
}
