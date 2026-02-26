<?php
namespace App\Repositories;

/**
 * Defines task persistence operations.
 * This keeps the service layer independent from Eloquent specifics.
 */
interface TaskRepositoryInterface {
    public function all(?string $status = null, int $page = 1, int $perPage = 12, ?int $viewerId = null, ?int $ownerId = null);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function reorder(array $orderedIds);
}
