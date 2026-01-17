<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine if the user can view any appointments.
     * Admins can view all, Clinicians can only view their own.
     */
    public function viewAny(User $user): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('view-appointment', $user->role_id)) {
            return false;
        }

        // Admins can view all appointments
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the appointment.
     * Admins can view all, Clinicians can only view their own.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('show-appointment', $user->role_id)) {
            return false;
        }

        // Admins can view all appointments
        if ($user->isAdmin()) {
            return true;
        }

        // Clinicians can only view their own appointments
        return $appointment->clinician_id === $user->id;
    }

    /**
     * Determine if the user can create appointments.
     */
    public function create(User $user): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('add-appointment', $user->role_id);
    }

    /**
     * Determine if the user can update the appointment.
     * Admins can update all, Clinicians can only update their own.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('edit-appointment', $user->role_id)) {
            return false;
        }

        // Admins can update all appointments
        if ($user->isAdmin()) {
            return true;
        }

        // Clinicians can only update their own appointments
        return $appointment->clinician_id === $user->id;
    }

    /**
     * Determine if the user can delete the appointment.
     * Admins can delete all, Clinicians can only delete their own.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('delete-appointment', $user->role_id)) {
            return false;
        }

        // Admins can delete all appointments
        if ($user->isAdmin()) {
            return true;
        }

        // Clinicians can only delete their own appointments
        return $appointment->clinician_id === $user->id;
    }

    /**
     * Determine if the user can restore the appointment.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('delete-appointment', $user->role_id)) {
            return false;
        }

        // Admins can restore all appointments
        if ($user->isAdmin()) {
            return true;
        }

        // Clinicians can only restore their own appointments
        return $appointment->clinician_id === $user->id;
    }

    /**
     * Determine if the user can permanently delete the appointment.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        // Only admins can permanently delete
        if (! $user->isAdmin()) {
            return false;
        }

        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('delete-appointment', $user->role_id);
    }
}
