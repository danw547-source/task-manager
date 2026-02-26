<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskEngagement\CommentTaskRequest;
use App\Http\Requests\TaskEngagement\ListTaskCommentsRequest;
use App\Models\Task;
use App\Services\TaskEngagementService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Handles follows, comments, and unread message actions for tasks.
 * This keeps collaboration features separate from core task CRUD endpoints.
 */
class TaskEngagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TaskEngagementService $engagementService)
    {
    }

    /**
     * Uses the current Passport user (`auth:api`) to follow a task.
     *
     * Follows are stored as a many-to-many relation, and the repository uses
     * `syncWithoutDetaching()` so repeated follow requests remain safe and idempotent.
     */
    public function follow(Request $request, Task $task)
    {
        // Keep ownership/role checks in TaskPolicy instead of duplicating them in controllers.
        $this->authorize('view', $task);
        $this->engagementService->followTask((int) $task->id, (int) $request->user()->id);

        return $this->successResponse(null, 'Task followed successfully');
    }

    /**
     * Removes the current user's follow edge from the task.
     */
    public function unfollow(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $this->engagementService->unfollowTask((int) $task->id, (int) $request->user()->id);

        return $this->successResponse(null, 'Task unfollowed successfully');
    }

    /**
     * Stores a task comment and fan-outs unread receipts to task owner + followers.
     *
     * CSRF note: these API endpoints are guarded by Passport bearer tokens (`auth:api`) and do
     * not rely on browser session cookies; because the Authorization header is explicitly set by
     * our frontend API client, cross-site form submissions cannot silently invoke these mutations.
     */
    public function comment(CommentTaskRequest $request, Task $task)
    {
        $this->authorize('view', $task);
        $payload = $request->validated();

        $comment = $this->engagementService->addComment(
            (int) $task->id,
            (int) $request->user()->id,
            $payload['body'],
            isset($payload['parent_comment_id']) ? (int) $payload['parent_comment_id'] : null
        );

        return $this->successResponse($comment, 'Comment added successfully', 201);
    }

    public function comments(ListTaskCommentsRequest $request, Task $task)
    {
        $this->authorize('view', $task);
        $payload = $request->validated();

        $paginated = $this->engagementService->listComments(
            (int) $task->id,
            (int) $request->user()->id,
            (int) ($payload['page'] ?? 1),
            (int) ($payload['per_page'] ?? 10)
        );

        return $this->paginatedResponse($paginated);
    }

    public function unreadMessages(Request $request)
    {
        $summary = $this->engagementService->unreadMessageSummary((int) $request->user()->id);

        return $this->successResponse($summary, 'Unread message summary retrieved successfully');
    }

    public function markTaskMessagesRead(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $marked = $this->engagementService->markTaskMessagesAsRead((int) $task->id, (int) $request->user()->id);

        return $this->successResponse(['marked' => $marked], 'Task messages marked as read');
    }
}
