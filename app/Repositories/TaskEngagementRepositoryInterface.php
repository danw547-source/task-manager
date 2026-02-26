<?php

namespace App\Repositories;

/**
 * Defines engagement data operations (follow, comments, unread receipts).
 * This keeps collaboration persistence behind a stable API.
 */
interface TaskEngagementRepositoryInterface
{
    public function followTask(int $taskId, int $userId): void;

    public function unfollowTask(int $taskId, int $userId): void;

    public function addComment(int $taskId, int $userId, string $body, ?int $parentCommentId = null);

    public function listComments(int $taskId, int $viewerId, int $page = 1, int $perPage = 10);

    public function unreadMessageSummary(int $userId): array;

    public function markTaskMessagesAsRead(int $taskId, int $userId): int;
}
