<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = [
            'budget_exceeded',
            'budget_near_limit',
            'goal_completed',
            'goal_near_completion',
            'receipt_pending',
            'receipt_confirmed',
            'low_balance',
        ];

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(2),
            'data' => [],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}