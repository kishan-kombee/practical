<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(EmailTemplateSeeder::class);
        $this->call(EmailFormatSeeder::class);
        $this->call(UserSeeder::class);

        $this->call(LoginHistorySeeder::class);

        $this->call(CategorySeeder::class);

        $this->call(SubCategorySeeder::class);

        $this->call(ProductSeeder::class);

        $this->call(AppointmentSeeder::class);

        $this->call(SmsTemplateSeeder::class);
    }
}
