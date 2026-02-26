<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates task update input.
 * Supports partial updates while preserving field-level rules.
 */
class UpdateTaskRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,done',
            'due_date' => 'prohibited',
            'user_id' => 'nullable|exists:users,id',
            'position' => 'nullable|integer|min:0',
        ];
    }
}
