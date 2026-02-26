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

    public function all(
        ?string $status = null,
        int $page = 1,
        int $perPage = 12,
        ?int $viewerId = null,
        ?int $ownerId = null,
        string $scope = 'all'
    )
    {
        // Repository is responsible for retrieval/filtering.
        // Service applies viewer-specific presentation flags used by the UI.
        $paginated = $this->taskRepo->all($status, $page, $perPage, $viewerId, $ownerId, $scope);

        if (!$viewerId) {
            return $paginated;
        }

        $taskIds = $paginated->getCollection()->pluck('id')->all();
        $followedTaskIds = $this->taskRepo->followingTaskIds($viewerId, $taskIds);
        $followedIndex = array_flip($followedTaskIds);

        $paginated->getCollection()->transform(function ($task) use ($followedIndex, $viewerId) {
            // Owners are treated as followers by default so the frontend can
            // rely on one consistent `is_following` flag.
            $task->is_following = (int) $task->user_id === (int) $viewerId
                || isset($followedIndex[(int) $task->id]);

            return $task;
        });

        return $paginated;
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
