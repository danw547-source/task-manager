<?php

namespace App\Http\Controllers;
use App\Http\Requests\Task\ReorderTasksRequest;
use App\Http\Requests\Task\SetReminderRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Jobs\SendTaskReminderNotification;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;

/**
 * Handles task CRUD and task-specific actions.
 * Uses requests + policies + service layer to keep HTTP, auth, and business logic separated.
 */
class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Task::class);

        $actor = request()->user();
        $viewerId = $actor?->id;
        $mine = filter_var(request()->query('mine', false), FILTER_VALIDATE_BOOLEAN);
        $requestedUserId = max(0, (int) request()->query('user_id', 0));

        $ownerId = null;
        if ($mine) {
            $ownerId = $viewerId ? (int) $viewerId : null;
        } elseif ($actor?->isAdmin() && $requestedUserId > 0) {
            $ownerId = $requestedUserId;
        }

        $paginated = $this->taskService->all(
            request()->query('status'),
            max(1, (int) request()->query('page', 1)),
            max(1, min(50, (int) request()->query('per_page', 12))),
            $viewerId ? (int) $viewerId : null,
            $ownerId
        );

        return $this->paginatedResponse($paginated);
    }

    public function store(StoreTaskRequest $request)
    {
        $this->authorize('create', Task::class);

        $actor = $request->user();
        $validated = $request->validated();

        // Consistency rule: non-admin users can only create tasks for themselves.
        if (!$actor?->isAdmin()) {
            unset($validated['user_id']);
            $validated['user_id'] = $actor?->id;
        }

        if (!isset($validated['user_id'])) {
            $validated['user_id'] = $actor?->id;
        }

        $task = $this->taskService->create($validated);
        return $this->successResponse($task, 'Task created successfully', 201);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return $this->successResponse($task, 'Task retrieved successfully');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $actor = $request->user();
        $validated = $request->validated();

        if (!$actor?->isAdmin()) {
            unset($validated['user_id']);
        }

        $updatedTask = $this->taskService->update($task->id, $validated);
        return $this->successResponse($updatedTask, 'Task updated successfully');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task->id);
        return $this->successResponse(null, 'Task deleted successfully');
    }

    public function reorder(ReorderTasksRequest $request)
    {
        $this->authorize('reorder', Task::class);

        $payload = $request->validated();
        $reordered = $this->taskService->reorder($payload['ordered_ids']);

        return $this->successResponse($reordered, 'Tasks reordered successfully');
    }

    public function setReminder(SetReminderRequest $request, Task $task)
    {
        $this->authorize('setReminder', $task);

        $payload = $request->validated();

        SendTaskReminderNotification::dispatch($task)
            ->delay(now()->addSeconds($payload['delay_seconds']));

        return $this->successResponse(
            ['delay_seconds' => $payload['delay_seconds']],
            'Reminder set successfully',
            201
        );
    }
}