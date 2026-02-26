<?php

namespace App\Services;

use App\Repositories\TaskEngagementRepositoryInterface;

/**
 * Contains collaboration features around tasks.
 * It gives us one place to add shared rules as engagement grows.
 */
class TaskEngagementService
{
    public function __construct(private readonly TaskEngagementRepositoryInterface $repository)
    {
    }

    public function followTask(int $taskId, int $userId): void
    {
        $this->repository->followTask($taskId, $userId);
    }

    public function unfollowTask(int $taskId, int $userId): void
    {
        $this->repository->unfollowTask($taskId, $userId);
    }

    public function addComment(int $taskId, int $userId, string $body, ?int $parentCommentId = null)
    {
        return $this->repository->addComment($taskId, $userId, $body, $parentCommentId);
    }

    public function listComments(int $taskId, int $viewerId, int $page = 1, int $perPage = 10)
    {
        return $this->repository->listComments($taskId, $viewerId, $page, $perPage);
    }

    public function unreadMessageSummary(int $userId): array
    {
        return $this->repository->unreadMessageSummary($userId);
    }

    public function markTaskMessagesAsRead(int $taskId, int $userId): int
    {
        return $this->repository->markTaskMessagesAsRead($taskId, $userId);
    }
}
