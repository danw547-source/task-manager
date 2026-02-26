<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentReceipt;
use App\Models\User;
use App\Notifications\TaskFollowedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Persists social and engagement features for tasks.
 * Uses transactions and receipt tables to keep comment and unread state consistent.
 */
class TaskEngagementRepository implements TaskEngagementRepositoryInterface
{
    public function followTask(int $taskId, int $userId): void
    {
        $task = Task::with('user:id,name,email')->findOrFail($taskId);
        $alreadyFollowing = $task->followers()->where('users.id', $userId)->exists();

        $task->followers()->syncWithoutDetaching([$userId]);

        if ($alreadyFollowing || (int) $task->user_id === $userId || !$task->user) {
            return;
        }

        $follower = User::query()->select('id', 'name', 'email')->find($userId);

        if (!$follower) {
            return;
        }

        $task->user->notify(new TaskFollowedNotification($task, $follower));
    }

    public function unfollowTask(int $taskId, int $userId): void
    {
        $task = Task::findOrFail($taskId);
        $task->followers()->detach($userId);
    }

    public function addComment(int $taskId, int $userId, string $body, ?int $parentCommentId = null)
    {
        return DB::transaction(function () use ($taskId, $userId, $body, $parentCommentId) {
            $task = Task::with('followers:id')->findOrFail($taskId);

            if ($parentCommentId !== null) {
                TaskComment::query()
                    ->where('task_id', $taskId)
                    ->where('id', $parentCommentId)
                    ->firstOrFail();
            }

            $comment = TaskComment::create([
                'task_id' => $taskId,
                'user_id' => $userId,
                'parent_comment_id' => $parentCommentId,
                'body' => $body,
            ]);

            $recipientIds = collect([$task->user_id])
                ->merge($task->followers->pluck('id'))
                ->filter()
                ->unique()
                ->reject(fn ($id) => (int) $id === $userId)
                ->values();

            if ($recipientIds->isNotEmpty()) {
                $rows = $recipientIds->map(fn ($recipientId) => [
                    'task_comment_id' => $comment->id,
                    'task_id' => $taskId,
                    'recipient_user_id' => (int) $recipientId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                TaskCommentReceipt::insert($rows);
            }

            return $comment->load('user:id,name,email');
        });
    }

    public function listComments(int $taskId, int $viewerId, int $page = 1, int $perPage = 10)
    {
        Task::findOrFail($taskId);

        return TaskComment::query()
            ->with('user:id,name,email')
            ->where('task_id', $taskId)
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function unreadMessageSummary(int $userId): array
    {
        $total = TaskCommentReceipt::query()
            ->where('recipient_user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $taskBreakdown = TaskCommentReceipt::query()
            ->selectRaw('task_id, COUNT(*) as unread_count')
            ->where('recipient_user_id', $userId)
            ->whereNull('read_at')
            ->groupBy('task_id')
            ->orderByDesc('unread_count')
            ->get()
            ->map(function ($row) {
                return [
                    'task_id' => (int) $row->task_id,
                    'unread_count' => (int) $row->unread_count,
                ];
            })
            ->values()
            ->all();

        return [
            'total_unread' => $total,
            'tasks' => $taskBreakdown,
        ];
    }

    public function markTaskMessagesAsRead(int $taskId, int $userId): int
    {
        return TaskCommentReceipt::query()
            ->where('recipient_user_id', $userId)
            ->where('task_id', $taskId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
