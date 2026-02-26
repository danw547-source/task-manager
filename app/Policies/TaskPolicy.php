<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Holds task authorization rules.
 * Using a policy keeps role and ownership checks consistent across controllers.
 */
class TaskPolicy
{
    /**
        * Admins can perform all task actions.
        * That keeps per-action methods focused on ownership rules for non-admin users.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Task $task): bool
    {
        /**
         * Keep read access aligned with existing application behavior where authenticated
         * users can browse tasks. Write/update/delete actions remain ownership-gated.
         */
        return $user !== null;
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, Task $task): bool
    {
        // Normalize IDs to integers to avoid strict-type mismatches (e.g., SQLite string IDs in tests).
        return (int) $task->user_id === (int) $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return (int) $task->user_id === (int) $user->id;
    }

    public function reorder(User $user): bool
    {
        return $user !== null;
    }

    public function setReminder(User $user, Task $task): bool
    {
        return (int) $task->user_id === (int) $user->id;
    }
}
