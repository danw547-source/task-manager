<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates task creation input.
 * Enforces task field constraints before business logic runs.
 */
class StoreTaskRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'nullable|in:pending,in_progress,done',
            'due_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'user_id' => 'nullable|exists:users,id',
            'position' => 'nullable|integer|min:0',
        ];
    }
}
