<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;

/**
 * Handles user-management use cases.
 * It keeps controller methods short and data access in one place.
 */
class UserService
{
    public function __construct(private readonly UserRepositoryInterface $userRepo)
    {
    }

    public function all()
    {
        return $this->userRepo->all();
    }

    public function options()
    {
        return $this->userRepo->options();
    }

    public function find($id)
    {
        return $this->userRepo->find($id);
    }

    public function create(array $data)
    {
        return $this->userRepo->create($data);
    }

    public function update($id, array $data)
    {
        return $this->userRepo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->userRepo->delete($id);
    }
}
