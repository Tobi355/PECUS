<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Source;
use App\Models\Category;
use App\Models\Movement;
use App\Models\Receipt;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a demo user
        $user = User::factory()->create([
            'name' => 'Demo',
            'surname' => 'User',
            'email' => 'demo@pecus.com',
            'password' => bcrypt('password'),
        ]);

        // Create default categories for the user (similar to what AuthController does)
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

        // Create some sources
        Source::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Create some additional categories
        Category::factory()->count(5)->create([
            'user_id' => $user->id,
            'is_default' => false,
        ]);

        // Create some movements
        Movement::factory()->count(20)->create([
            'user_id' => $user->id,
        ]);

        // Create some receipts
        Receipt::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);
    }
}