<?php

namespace App\Http\Requests\TaskEngagement;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates task comment creation input.
 * Keeps nested-comment integrity checks at the request boundary.
 */
class CommentTaskRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string|min:1|max:2000',
            'parent_comment_id' => 'nullable|integer|exists:task_comments,id',
        ];
    }
}
