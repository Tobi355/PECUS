<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'settings' => [
                'push_notifications' => $this->faker->boolean(),
                'email_notifications' => $this->faker->boolean(),
                'dark_mode' => $this->faker->boolean(),
                'default_currency' => $this->faker->randomElement(['ARS', 'USD', 'EUR']),
                'auto_login' => $this->faker->boolean(),
                'auto_sync' => $this->faker->boolean(),
                'backups' => $this->faker->boolean(),
            ],
        ];
    }
}