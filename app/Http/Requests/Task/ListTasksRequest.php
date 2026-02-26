<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates task list query params.
 *
 * We keep list-filter rules here so controller code can focus on
 * authorization + orchestration rather than parsing query strings.
 */
class ListTasksRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:pending,in_progress,done',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            // Legacy support: older clients still send `mine=1`.
            'mine' => 'nullable|boolean',
            'user_id' => 'nullable|integer|min:1',
            // Primary filter mode used by current UI.
            'scope' => 'nullable|in:all,owned,following',
        ];
    }
}
