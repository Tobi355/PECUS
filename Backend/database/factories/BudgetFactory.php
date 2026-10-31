<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $endDate = $this->faker->boolean(70)
            ? $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d')
            : null;

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => 'EXPENSE']),
            'period' => $this->faker->randomElement(['WEEKLY', 'MONTHLY', 'YEARLY']),
            'amount' => $this->faker->numberBetween(5000, 200000),
            'currency' => 'ARS',
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'end_date' => $endDate,
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'MONTHLY',
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'WEEKLY',
            'start_date' => now()->startOfWeek()->format('Y-m-d'),
            'end_date' => now()->endOfWeek()->format('Y-m-d'),
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'YEARLY',
            'start_date' => now()->startOfYear()->format('Y-m-d'),
            'end_date' => now()->endOfYear()->format('Y-m-d'),
        ]);
    }
}