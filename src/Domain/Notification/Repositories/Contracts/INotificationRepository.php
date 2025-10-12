<?php

namespace Domain\Notification\Repositories\Contracts;

use Core\Http\Requests\TableRequest;
use Domain\Notification\Models\Notification;
use Domain\Plan\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface INotificationRepository.
 */
interface INotificationRepository
{
    /**
     * Get the notifications pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator;

    /**
     * Get the unread notifications
     * @return Collection
     */
    public function unread() :Collection;

    /**
     * Get the unread notifications
     * @return array
     */
    public function readAll() :array;

    /**
     * Get the notification.
     * @param Notification $notification
     * @return Plan
     */
    public function show(Notification $notification) :Notification;
}