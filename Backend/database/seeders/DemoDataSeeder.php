<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Source;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or reuse the demo user so repeated seed runs remain safe.
        $user = User::firstOrCreate(
            ['email' => 'juan.perez@email.com'],
            [
                'name' => 'Juan',
                'surname' => 'Pérez',
                'password' => Hash::make('password'),
            ]
        );

        $user->update([
            'name' => 'Juan',
            'surname' => 'Pérez',
            'password' => Hash::make('password'),
        ]);

        // Create or reuse the demo source for the user.
        Source::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Mercado Pago', 'type' => 'WALLET'],
            [
                'currency' => 'ARS',
                'initial_balance' => 0.00,
                'status' => 'ACTIVE',
            ]
        );

        // Create default categories for expenses
        $expenseCategories = [
            ['name' => 'Alimentación', 'type' => 'EXPENSE', 'icon' => 'utensils', 'color' => '#dc3545', 'is_default' => true],
            ['name' => 'Transporte', 'type' => 'EXPENSE', 'icon' => 'car', 'color' => '#ffc107', 'is_default' => true],
            ['name' => 'Vivienda', 'type' => 'EXPENSE', 'icon' => 'home', 'color' => '#6f42c1', 'is_default' => true],
            ['name' => 'Servicios', 'type' => 'EXPENSE', 'icon' => 'zap', 'color' => '#20c997', 'is_default' => true],
            ['name' => 'Salud', 'type' => 'EXPENSE', 'icon' => 'heart-pulse', 'color' => '#fd7e14', 'is_default' => true],
            ['name' => 'Educación', 'type' => 'EXPENSE', 'icon' => 'book-open', 'color' => '#d63384', 'is_default' => true],
            ['name' => 'Entretenimiento', 'type' => 'EXPENSE', 'icon' => 'film', 'color' => '#0dcaf0', 'is_default' => true],
            ['name' => 'Compras', 'type' => 'EXPENSE', 'icon' => 'shopping-cart', 'color' => '#ffc107', 'is_default' => true],
            ['name' => 'Suscripciones', 'type' => 'EXPENSE', 'icon' => 'clock', 'color' => '#6610f2', 'is_default' => true],
            ['name' => 'Impuestos', 'type' => 'EXPENSE', 'icon' => 'file-text', 'color' => '#dc3545', 'is_default' => true],
            ['name' => 'Otros', 'type' => 'EXPENSE', 'icon' => 'more-horizontal', 'color' => '#6c757d', 'is_default' => true],
        ];

        foreach ($expenseCategories as $categoryData) {
            Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => $categoryData['name'], 'type' => $categoryData['type']],
                array_merge($categoryData, ['user_id' => $user->id])
            );
        }

        // Create default categories for income
        $incomeCategories = [
            ['name' => 'Sueldo', 'type' => 'INCOME', 'icon' => 'banknote', 'color' => '#28a745', 'is_default' => true],
            ['name' => 'Freelance', 'type' => 'INCOME', 'icon' => 'briefcase', 'color' => '#20c997', 'is_default' => true],
            ['name' => 'Ventas', 'type' => 'INCOME', 'icon' => 'shopping-bag', 'color' => '#ffc107', 'is_default' => true],
            ['name' => 'Inversiones', 'type' => 'INCOME', 'icon' => 'trending-up', 'color' => '#28a745', 'is_default' => true],
            ['name' => 'Otros', 'type' => 'INCOME', 'icon' => 'more-horizontal', 'color' => '#6c757d', 'is_default' => true],
        ];

        foreach ($incomeCategories as $categoryData) {
            Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => $categoryData['name'], 'type' => $categoryData['type']],
                array_merge($categoryData, ['user_id' => $user->id])
            );
        }
    }
}
