<?php

namespace App\Http\Requests\TaskEngagement;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates pagination params for task comments.
 * Applies list endpoint constraints before repository queries run.
 */
class ListTaskCommentsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }
}
