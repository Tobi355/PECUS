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
        // This seeder is intentionally disabled to avoid creating test users in a clean database.
        $this->command?->info('Factory test seeder disabled.');
    }
}