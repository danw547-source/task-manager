<?php

namespace App\Services;

use App\Repositories\TaskRepositoryInterface;

class TaskService
{
    protected $taskRepo; // TaskRepositoryInterface, because we want to use the interface, not the implementation

    public function __construct(TaskRepositoryInterface $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    public function getAllTasks(?string $status = null, int $page = 1, int $perPage = 12)
    {
        return $this->taskRepo->all($status, $page, $perPage);
    }

    public function createTask($data)
    {
        return $this->taskRepo->create($data);
    }

    public function updateTask($id, $data)
    {
        return $this->taskRepo->update($id, $data);
    }

    public function deleteTask($id)
    {
        return $this->taskRepo->delete($id);
    }

    public function reorderTasks(array $orderedIds)
    {
        return $this->taskRepo->reorder($orderedIds);
    }
}
