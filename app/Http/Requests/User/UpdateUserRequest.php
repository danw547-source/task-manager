<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiFormRequest;

/**
 * Validates admin user-update input.
 * Handles unique email checks while excluding the current user record.
 */
class UpdateUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('user');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|in:user,admin',
        ];
    }
}
