<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin role
        $adminRole = Role::where('name', 'Admin')->first();

        if ($adminRole) {
            // Create a default admin user
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'role_id' => $adminRole->id,
                    'first_name' => 'Admin',
                    'last_name' => 'User',
                    'email' => 'admin@example.com',
                    'mobile_number' => '6123456789',
                    'password' => Hash::make('password'),
                    'status' => 'Y',
                    'locale' => 'en',
                ]
            );
        }

        // Create additional users
        User::factory()
            ->count(10)
            ->active()
            ->create();

        // Create a few inactive users
        User::factory()
            ->count(2)
            ->inactive()
            ->create();
    }
}
