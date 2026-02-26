<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base request class for API endpoints.
 * Keeps validation errors in one consistent JSON format.
 */
abstract class ApiFormRequest extends FormRequest
{
    /**
     * API endpoints in this project use a shared JSON envelope.
     * We override Laravel's default validation response so 422 errors
     * stay consistent with ApiResponse trait output.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'status' => 422,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }
}
