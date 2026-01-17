<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active users (clinicians)
        $clinicians = User::where('status', 'Y')
            ->whereNull('deleted_at')
            ->get();

        if ($clinicians->isEmpty()) {
            $this->command->warn('No active users found. Please run UserSeeder first.');
            return;
        }

        // Create appointments for each clinician
        foreach ($clinicians as $clinician) {
            // Create 3-5 booked appointments (future)
            Appointment::factory()
                ->count(rand(3, 5))
                ->forClinician($clinician->id)
                ->booked()
                ->future()
                ->create();

            // Create 2-3 completed appointments (past)
            Appointment::factory()
                ->count(rand(2, 3))
                ->forClinician($clinician->id)
                ->completed()
                ->past()
                ->create();

            // Create 1-2 cancelled appointments
            Appointment::factory()
                ->count(rand(1, 2))
                ->forClinician($clinician->id)
                ->cancelled()
                ->create();
        }

        // Create some additional random appointments
        Appointment::factory()
            ->count(10)
            ->booked()
            ->create();
    }
}
