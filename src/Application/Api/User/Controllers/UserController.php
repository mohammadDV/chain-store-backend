<?php

namespace Application\Api\User\Controllers;

use Application\Api\User\Requests\ChangePasswordRequest;
use Application\Api\User\Requests\UpdateUserRequest;
use Core\Http\Controllers\Controller;
use Domain\User\Models\User;
use Domain\User\Repositories\Contracts\IUserRepository;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Constructor of UserController.
     */
    public function __construct(protected  IUserRepository $repository)
    {
        //
    }

    /**
     * Handle an incoming authentication request.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->repository->index());
    }

    /**
     * Handle an incoming authentication request.
     */
    public function getActivityCount(): JsonResponse
    {
        return response()->json($this->repository->getActivityCount());
    }

    /**
     * Get the dashboard info.
     */
    public function getDashboardInfo(): JsonResponse
    {
        return response()->json($this->repository->getDashboardInfo());
    }



    /**
     * Get the user info
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        return response()->json($this->repository->show());
    }

    /**
     * Get the user info
     * @param User $user
     * @return JsonResponse
     */
    public function getUserInfo(User $user): JsonResponse
    {
        return response()->json($this->repository->getUserInfo($user));
    }

    /**
     * Get the user info
     * @return JsonResponse
     */
    public function checkVerification(): JsonResponse
    {
        return response()->json($this->repository->checkVerification());
    }

     /**
     * Update the user.
     * @param UpdateUserRequest $request
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request) :JsonResponse
    {
        return response()->json($this->repository->update($request));
    }

    /**
     * Change the user password.
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request) :JsonResponse
    {
        return response()->json($this->repository->changePassword($request));
    }
}
