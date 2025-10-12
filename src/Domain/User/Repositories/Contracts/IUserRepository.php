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
    public function index() :JsonResponse;

    /**
     * Get the dashboard info
     *
     * @return array The seller object
     */
    public function getDashboardInfo() :array;

    /**
     * Get the user info.
     * @return array
     */
    public function show() :array;

    /**
     * Get the user info.
     * @param User $user
     * @return array
     */
    public function getUserInfo(User $user) :array;

    /**
     * Get verification of the user
     * @return array
     */
    public function checkVerification() :array;

    /**
     * Update the user.
     * @param UpdateUserRequest $request
     * @return array
     */
    public function update(UpdateUserRequest $request) :array;

    /**
     * Change the user password.
     * @param ChangePasswordRequest $request
     * @return array
     */
    public function changePassword(ChangePasswordRequest $request) :array;
}
