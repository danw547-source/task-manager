<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates drag-and-drop reorder input.
 * Ensures every provided ID maps to a real task record.
 */
class ReorderTasksRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer|exists:tasks,id',
        ];
    }
}
