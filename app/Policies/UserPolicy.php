<?php

namespace App\Policies;

use App\Models\User;

/**
 * Holds admin-only authorization rules for user management.
 * Using a policy avoids scattered role checks across endpoints.
 */
class UserPolicy
{
    /**
     * User management endpoints are admin-only by design.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
