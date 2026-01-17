<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_name' => $this->faker->name(),
            'clinic_location' => $this->faker->address(),
            'clinician_id' => User::factory(),
            'appointment_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['B', 'D', 'N']), // B => Booked, D => Completed, N => Cancelled
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the appointment is booked.
     */
    public function booked(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'B',
        ]);
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'D',
        ]);
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'N',
        ]);
    }

    /**
     * Set the clinician for this appointment.
     */
    public function forClinician(int $clinicianId): static
    {
        return $this->state(fn(array $attributes) => [
            'clinician_id' => $clinicianId,
        ]);
    }

    /**
     * Set appointment date in the future.
     */
    public function future(): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_date' => $this->faker->dateTimeBetween('+1 day', '+3 months')->format('Y-m-d'),
            'status' => 'B', // Future appointments are typically booked
        ]);
    }

    /**
     * Set appointment date in the past.
     */
    public function past(): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_date' => $this->faker->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['D', 'N']), // Past appointments are typically completed or cancelled
        ]);
    }
}
