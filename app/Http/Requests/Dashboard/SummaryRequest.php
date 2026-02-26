<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\ApiFormRequest;

class SummaryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'months' => 'nullable|integer|min:1|max:24',
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && !$this->user()?->isAdmin()) {
                        $fail('The selected user_id is invalid.');
                    }
                },
            ],
        ];
    }
}
