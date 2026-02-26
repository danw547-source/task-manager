<?php

namespace App\Repositories;

/**
 * Defines user persistence operations.
 * The interface supports testing and easy implementation swaps via DI.
 */
interface UserRepositoryInterface
{
    public function all();
    public function options();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
