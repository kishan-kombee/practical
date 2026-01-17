<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Policy class for Product authorization.
 *
 * Defines authorization rules for product-related actions.
 */
class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('view-product', $user->role_id);
    }

    /**
     * Determine if the user can view the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function view(User $user, Product $product): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('show-product', $user->role_id);
    }

    /**
     * Determine if the user can create products.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('add-product', $user->role_id);
    }

    /**
     * Determine if the user can update the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function update(User $user, Product $product): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('edit-product', $user->role_id);
    }

    /**
     * Determine if the user can delete the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function delete(User $user, Product $product): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('delete-product', $user->role_id);
    }

    /**
     * Determine if the user can restore the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function restore(User $user, Product $product): bool
    {
        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('delete-product', $user->role_id);
    }

    /**
     * Determine if the user can permanently delete the product.
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Only admins can permanently delete
        if (!$user->isAdmin()) {
            return false;
        }

        // Check if user has permission via Gate (backward compatibility)
        return $user->hasPermission('delete-product', $user->role_id);
    }
}
