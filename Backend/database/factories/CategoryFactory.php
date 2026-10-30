<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['EXPENSE', 'INCOME'];
        $icons = ['utensils', 'car', 'home', 'zap', 'heart-pulse', 'more-horizontal', 'banknote', 'briefcase', 'gift', 'food'];
        $colors = ['#dc3545', '#ffc107', '#6f42c1', '#20c997', '#fd7e14', '#6c757d', '#28a745', '#17a2b8', '#ffc107', '#e83e8c'];

        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->word . ' ' . $this->faker->word,
            'type' => $this->faker->randomElement($types),
            'icon' => $this->faker->randomElement($icons),
            'color' => $this->faker->randomElement($colors),
            'is_default' => $this->faker->boolean(10), // 10% chance of being default
        ];
    }
}