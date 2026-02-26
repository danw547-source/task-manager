<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates reminder scheduling input.
 * Restricts delay values to supported notification timings.
 */
class SetReminderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'delay_seconds' => 'required|integer|in:10,20,60',
        ];
    }
}
