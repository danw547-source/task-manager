<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;

/**
 * Eloquent-backed user repository.
 * Keeps user reads and writes in one place for consistent behavior.
 */
class UserRepository implements UserRepositoryInterface
{
    public function all()
    {
        return User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->get();
    }

    public function options()
    {
        return User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    public function find($id)
    {
        return User::find($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        return $user->delete();
    }
}
