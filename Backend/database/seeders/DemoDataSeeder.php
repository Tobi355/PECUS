<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Source;
use App\Models\Category;
use App\Models\Movement;
use App\Models\Receipt;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo data.
     */
    public function run(): void
    {
        // Create multiple demo users
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            // Create default categories for each user
            $defaultCategories = [
                ['name' => 'Alimentación', 'type' => 'EXPENSE', 'icon' => 'utensils', 'color' => '#dc3545'],
                ['name' => 'Transporte', 'type' => 'EXPENSE', 'icon' => 'car', 'color' => '#ffc107'],
                ['name' => 'Vivienda', 'type' => 'EXPENSE', 'icon' => 'home', 'color' => '#6f42c1'],
                ['name' => 'Servicios', 'type' => 'EXPENSE', 'icon' => 'zap', 'color' => '#20c997'],
                ['name' => 'Salud', 'type' => 'EXPENSE', 'icon' => 'heart-pulse', 'color' => '#fd7e14'],
                ['name' => 'Otros gastos', 'type' => 'EXPENSE', 'icon' => 'more-horizontal', 'color' => '#6c757d'],
                ['name' => 'Sueldo', 'type' => 'INCOME', 'icon' => 'banknote', 'color' => '#28a745'],
                ['name' => 'Otros ingresos', 'type' => 'INCOME', 'icon' => 'more-horizontal', 'color' => '#20c997'],
            ];

            foreach ($defaultCategories as $category) {
                $user->categories()->create(array_merge($category, ['is_default' => true]));
            }

            // Create sources for each user
            Source::factory()->count(rand(2, 5))->create([
                'user_id' => $user->id,
            ]);

            // Create additional categories for each user
            Category::factory()->count(rand(5, 10))->create([
                'user_id' => $user->id,
                'is_default' => false,
            ]);

            // Create movements for each user
            Movement::factory()->count(rand(10, 30))->create([
                'user_id' => $user->id,
            ]);

            // Create receipts for each user
            Receipt::factory()->count(rand(3, 8))->create([
                'user_id' => $user->id,
            ]);
        }
    }
}