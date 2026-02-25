<?php

namespace App\Http\Controllers;
use App\Services\TaskService;
use Illuminate\Http\Request;

// A controller should handle the request and return a response. It should not contain business logic or interact with the database directly. It should delegate those responsibilities to other classes, such as services or repositories. This keeps the controller thin and focused on its primary responsibility, which is to handle HTTP requests and responses.

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService) // To inject the TaskService into the controller, allowing it to handle business logic related to tasks. This promotes separation of concerns and keeps the controller thin. By using dependency injection, we can easily swap out the TaskService for a different implementation if needed, making our code more flexible and testable.
    {
        $this->taskService = $taskService;
    }

    public function index()
    {
        $status = request()->query('status');
        $page = max(1, (int) request()->query('page', 1));
        $perPage = max(1, min(50, (int) request()->query('per_page', 12)));

        return response()->json($this->taskService->getAllTasks($status, $page, $perPage));
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|in:pending,in_progress,done',
                'due_date' => 'nullable|date',
                'user_id' => 'nullable|exists:users,id',
                'position' => 'nullable|integer|min:0',
            
            ]);

        return response()->json($this->taskService->createTask($request->all()));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,done',
            'due_date' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'position' => 'nullable|integer|min:0',
        ]);

        return response()->json($this->taskService->updateTask($id, $request->all()));
    }

    public function destroy($id)
    {
         return response()->json($this->taskService->deleteTask($id));
    }

    public function reorder(Request $request)
    {
        $payload = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer|exists:tasks,id',
        ]);

        return response()->json($this->taskService->reorderTasks($payload['ordered_ids']));
    }

}