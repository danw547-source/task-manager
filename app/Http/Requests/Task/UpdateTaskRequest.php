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
            'due_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:today',
            'user_id' => 'nullable|exists:users,id',
            'position' => 'nullable|integer|min:0',
        ];
    }
}
