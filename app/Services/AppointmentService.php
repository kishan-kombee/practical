<?php

namespace App\Services;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Contracts\AppointmentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for Appointment business logic.
 *
 * This service handles all business logic related to appointments,
 * keeping controllers thin and focused on HTTP concerns.
 */
class AppointmentService implements AppointmentServiceInterface
{
    /**
     * Cache TTL for light data (24 hours).
     */
    private const CACHE_TTL = 86400;

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
    ) {
        if ($isLight) {
            return $this->getLightData();
        }

        $query = Appointment::query()
            ->select(['appointments.id', 'appointments.patient_name', 'appointments.clinic_location', 
                     'appointments.clinician_id', 'appointments.appointment_date', 'appointments.status',
                     'appointments.created_at', 'appointments.updated_at']);

        // Apply authorization: Clinicians see only their appointments, Admins see all
        $user = Auth::user();
        if ($user) {
            // Eager load role to avoid N+1 queries
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }
            if (!$user->isAdmin()) {
                $query->forClinician($user->id);
            }
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('appointments.patient_name', 'like', "%{$search}%")
                    ->orWhere('appointments.clinic_location', 'like', "%{$search}%")
                    ->orWhere('appointments.appointment_date', 'like', "%{$search}%")
                    ->orWhere('appointments.status', 'like', "%{$search}%");
            });
        }

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->where('appointments.' . $key, '=', $value);
            }
        }

        // Apply sorting
        $query->orderBy('appointments.' . $sortBy, $sortOrder);

        // Eager load relationships to prevent N+1 queries
        $query->with(['clinician:id,first_name,last_name,email']);

        // Apply pagination
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get light data (cached).
     *
     * @return Collection
     */
    private function getLightData(): Collection
    {
        return Cache::remember('appointment.all', self::CACHE_TTL, function () {
            $appointment = new Appointment();
            /** @var array<string> $lightFields */
            $lightFields = $appointment->light ?? [];
            
            return Appointment::select($lightFields)->get();
        });
    }

    /**
     * Get a single appointment by ID.
     *
     * @param int $id
     * @return Appointment|null
     */
    public function getById(int $id): ?Appointment
    {
        return Appointment::with(['clinician:id,first_name,last_name,email'])
            ->find($id);
    }

    /**
     * Create a new appointment.
     *
     * @param array<string, mixed> $data
     * @return Appointment
     */
    public function create(array $data): Appointment
    {
        // Ensure non-admins can only create appointments for themselves
        $user = Auth::user();
        if ($user && !$user->isAdmin()) {
            $data['clinician_id'] = $user->id;
        }

        $appointment = Appointment::create($data);

        // Invalidate cache
        Cache::forget('appointment.all');

        // Eager load relationships
        $appointment->load(['clinician:id,first_name,last_name,email']);

        return $appointment;
    }

    /**
     * Update an existing appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return Appointment
     */
    public function update(Appointment $appointment, array $data): Appointment
    {
        // Additional check: Non-admins cannot change the clinician_id
        $user = Auth::user();
        if ($user && !$user->isAdmin()) {
            $data['clinician_id'] = $appointment->clinician_id;
        }

        $appointment->update($data);

        // Invalidate cache
        Cache::forget('appointment.all');

        // Eager load relationships
        $appointment->load(['clinician:id,first_name,last_name,email']);

        return $appointment;
    }

    /**
     * Delete an appointment.
     *
     * @param Appointment $appointment
     * @return bool
     */
    public function delete(Appointment $appointment): bool
    {
        $deleted = $appointment->delete();

        // Invalidate cache
        if ($deleted) {
            Cache::forget('appointment.all');
        }

        return $deleted;
    }

    /**
     * Delete multiple appointments.
     *
     * @param array<int> $ids
     * @return int Number of deleted appointments
     */
    public function deleteMultiple(array $ids): int
    {
        $deletedCount = 0;
        $user = Auth::user();

        $appointments = Appointment::whereIn('id', $ids)->get();

        foreach ($appointments as $appointment) {
            // Check authorization for each appointment
            if ($user && !$user->can('delete', $appointment)) {
                continue;
            }

            if ($appointment->delete()) {
                $deletedCount++;
            }
        }

        // Invalidate cache if any were deleted
        if ($deletedCount > 0) {
            Cache::forget('appointment.all');
        }

        return $deletedCount;
    }
}
