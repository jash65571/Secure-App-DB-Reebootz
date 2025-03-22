<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'superadmin',
                'description' => 'Full access to all system features',
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access to system features',
            ],
            [
                'name' => 'Warehouse',
                'slug' => 'warehouse',
                'description' => 'Warehouse manager access',
            ],
            [
                'name' => 'Store',
                'slug' => 'store',
                'description' => 'Retail store access',
            ],
            [
                'name' => 'Customer',
                'slug' => 'customer',
                'description' => 'Customer/mobile app access',
            ],
        ];

        foreach ($roles as $role) {
            // Check if the role already exists before creating it
            if (!Role::where('slug', $role['slug'])->exists()) {
                Role::create($role);
            }
        }
    }
}
