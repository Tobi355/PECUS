<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['BANK', 'CREDIT_CARD', 'WALLET', 'CASH', 'OTHER'];
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD'];
        $statuses = ['ACTIVE', 'INACTIVE'];

        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement($types),
            'currency' => $this->faker->randomElement($currencies),
            'initial_balance' => $this->faker->randomFloat(2, 0, 10000),
            'status' => $this->faker->randomElement($statuses),
        ];
    }
}