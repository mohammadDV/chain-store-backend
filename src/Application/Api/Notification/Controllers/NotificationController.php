<?php

namespace Application\Api\Notification\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Notification\Models\Notification;
use Domain\Notification\Repositories\Contracts\INotificationRepository;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        protected INotificationRepository $repository,
    ) {
        //
    }

    /**
     * Get the notifications pagination.
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request));
    }

    /**
     * Get the notification.
     */
    public function show(Notification $notification): JsonResponse
    {
        return response()->json($this->repository->show($notification));
    }

    /**
     * Get the unread notifications.
     */
    public function unread(): JsonResponse
    {
        return response()->json($this->repository->unread());
    }

    /**
     * Mark all notifications as read.
     */
    public function readAll(): JsonResponse
    {
        return response()->json($this->repository->readAll());
    }
}
