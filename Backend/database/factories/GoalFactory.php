<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'target_amount' => $this->faker->numberBetween(10000, 1000000),
            'currency' => 'ARS',
            'target_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'current_amount' => 0,
            'status' => 'ACTIVE',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'COMPLETED',
            'current_amount' => $attributes['target_amount'],
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ARCHIVED',
        ]);
    }
}