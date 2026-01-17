<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('view-user', $user->role_id);
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('show-user', $user->role_id)) {
            return false;
        }

        // Admins can view all users
        if ($user->isAdmin()) {
            return true;
        }

        // Clinicians can only view themselves
        return $user->id === $model->id;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('add-user', $user->role_id);
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('edit-user', $user->role_id)) {
            return false;
        }

        // Admins can update all users
        if ($user->isAdmin()) {
            return true;
        }

        // Clinicians can only update themselves
        return $user->id === $model->id;
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('delete-user', $user->role_id)) {
            return false;
        }

        // Admins can delete all users (except themselves)
        if ($user->isAdmin()) {
            return $user->id !== $model->id;
        }

        // Clinicians cannot delete any users
        return false;
    }

    /**
     * Determine if the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        if (! $user->hasPermission('delete-user', $user->role_id)) {
            return false;
        }

        // Only admins can restore users
        return $user->isAdmin();
    }

    /**
     * Determine if the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only admins can permanently delete users
        if (! $user->isAdmin()) {
            return false;
        }

        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('delete-user', $user->role_id);
    }
}
