<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders in specific order
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
