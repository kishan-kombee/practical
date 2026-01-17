<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles
        $roles = [
            ['name' => 'Admin', 'status' => 'Y'],
            ['name' => 'Manager', 'status' => 'Y'],
            ['name' => 'Clinician', 'status' => 'Y'],
            ['name' => 'User', 'status' => 'Y'],
            ['name' => 'Guest', 'status' => 'Y'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        // Create additional random roles using factory
        Role::factory()
            ->count(5)
            ->active()
            ->create();
    }
}
