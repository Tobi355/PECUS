<?php

namespace Database\Factories;

use App\Models\Movement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['EXPENSE', 'INCOME', 'INTERNAL_TRANSFER', 'EXTERNAL_TRANSFER', 'REFUND'];
        $statuses = ['PENDING', 'CONFIRMED'];
        $origins = ['MANUAL', 'RECEIPT', 'SHARED', 'CSV', 'API', 'AUTOMATIC'];
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD'];

        return [
            'user_id' => \App\Models\User::factory(),
            'source_id' => \App\Models\Source::factory(),
            'category_id' => \App\Models\Category::factory(),
            'type' => $this->faker->randomElement($types),
            'status' => $this->faker->randomElement($statuses),
            'origin' => $this->faker->randomElement($origins),
            'amount' => $this->faker->randomFloat(2, 0.01, 1000),
            'currency' => $this->faker->randomElement($currencies),
            'description' => $this->faker->sentence,
            'operation_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->paragraph,
        ];
    }
}