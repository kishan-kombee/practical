<?php

namespace App\Services\Contracts;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Appointment Service Interface
 *
 * Defines the contract for Appointment service implementations.
 */
interface AppointmentServiceInterface
{
    /**
     * Get all appointments with filters, search, and pagination.
     *
     * @param array<string, mixed> $filters
     * @param string|null $search
     * @param string $sortBy
     * @param string $sortOrder
     * @param int $perPage
     * @param int $page
     * @param bool $isLight
     * @return Collection|LengthAwarePaginator
     */
    public function getAll(
        array $filters = [],
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'DESC',
        int $perPage = 15,
        int $page = 1,
        bool $isLight = false
    );

    /**
     * Get a single appointment by ID.
     *
     * @param int $id
     * @return Appointment|null
     */
    public function getById(int $id): ?Appointment;

    /**
     * Create a new appointment.
     *
     * @param array<string, mixed> $data
     * @return Appointment
     */
    public function create(array $data): Appointment;

    /**
     * Update an existing appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return Appointment
     */
    public function update(Appointment $appointment, array $data): Appointment;

    /**
     * Delete an appointment.
     *
     * @param Appointment $appointment
     * @return bool
     */
    public function delete(Appointment $appointment): bool;
}
