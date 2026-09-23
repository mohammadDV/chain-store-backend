<?php

namespace Domain\User\Repositories\Contracts;

use Application\Api\User\Requests\ChangePasswordRequest;
use Application\Api\User\Requests\UpdateUserRequest;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Interface IUserRepository.
 */
interface IUserRepository
{
    /**
     * Get the users
     *
     * @return JsonResponse The seller object
     */
    public function index(): JsonResponse;

    /**
     * Get the dashboard info
     *
     * @return array The seller object
     */
    public function getDashboardInfo(): array;

    /**
     * Get the user info.
     */
    public function show(): array;

    /**
     * Get the user info.
     */
    public function getUserInfo(User $user): array;

    /**
     * Get verification of the user
     */
    public function checkVerification(): array;

    /**
     * Update the user.
     */
    public function update(UpdateUserRequest $request): array;

    /**
     * Change the user password.
     */
    public function changePassword(ChangePasswordRequest $request): array;
}
