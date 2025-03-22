<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Super Admin role
        $superAdminRole = Role::where('slug', 'superadmin')->first();

        if ($superAdminRole) {
            // Create Super Admin user
            User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
                'first_login' => false,
            ]);
        }

        // Get Admin role
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole) {
            // Create Admin user
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'first_login' => false,
            ]);
        }
    }
}
