<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Source;
use App\Models\Category;
use App\Models\Movement;
use App\Models\Receipt;

class TestFactorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Test User factory
        $user = User::factory()->create([
            'email' => 'test@factory.com'
        ]);

        // Test Source factory
        $source = Source::factory()->create([
            'user_id' => $user->id
        ]);

        // Test Category factory
        $category = Category::factory()->create([
            'user_id' => $user->id
        ]);

        // Test Movement factory
        $movement = Movement::factory()->create([
            'user_id' => $user->id,
            'source_id' => $source->id,
            'category_id' => $category->id
        ]);

        // Test Receipt factory
        $receipt = Receipt::factory()->create([
            'user_id' => $user->id
        ]);

        // Just to have some output
        $this->command->info('Factory test completed successfully!');
    }
}