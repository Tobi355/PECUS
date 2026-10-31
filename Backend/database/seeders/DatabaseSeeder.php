<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The project intentionally avoids demo data in the default database setup.
        // Empty seeders keep a clean base database for exports and fresh installs.
        $this->command?->info('No demo data seeded by default.');
    }
}