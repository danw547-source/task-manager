<?php

namespace App\Services;

use App\Repositories\TaskRepositoryInterface;

/**
 * Handles task operations used by controllers.
 * This keeps business flow stable even when persistence details change.
 */
class TaskService
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepo)
    {
    }

    public function all(?string $status = null, int $page = 1, int $perPage = 12, ?int $viewerId = null, ?int $ownerId = null)
    {
        return $this->taskRepo->all($status, $page, $perPage, $viewerId, $ownerId);
    }

    public function find($id)
    {
        return $this->taskRepo->find($id);
    }

    public function create($data)
    {
        return $this->taskRepo->create($data);
    }

    public function update($id, $data)
    {
        return $this->taskRepo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->taskRepo->delete($id);
    }

    public function reorder(array $orderedIds)
    {
        return $this->taskRepo->reorder($orderedIds);
    }
}
