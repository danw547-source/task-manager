<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;

/**
 * Handles admin user-management endpoints.
 * CRUD HTTP logic lives here, while access rules stay in policy and middleware.
 */
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly UserService $userService)
    {
    }

    public function options()
    {
        $users = $this->userService->options();

        return $this->successResponse($users, 'User options retrieved successfully');
    }

    public function index()
    {
        // Even though route middleware already limits this to admins,
        // policy checks keep authorization rules explicit and testable.
        $this->authorize('viewAny', User::class);
        $users = $this->userService->all();
        return $this->successResponse($users, 'Users retrieved successfully');
    }

    public function show($id)
    {
        $this->authorize('view', User::class);
        $user = $this->userService->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse($user, 'User retrieved successfully');
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        $user = $this->userService->create($validated);

        return $this->successResponse($user, 'User created successfully', 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $this->authorize('update', User::class);

        $user = $this->userService->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $validated = $request->validated();
        $updatedUser = $this->userService->update($id, $validated);

        return $this->successResponse($updatedUser, 'User updated successfully');
    }

    public function destroy($id)
    {
        $this->authorize('delete', User::class);
        $user = $this->userService->find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $this->userService->delete($id);
        return $this->successResponse(null, 'User deleted successfully');
    }
}
