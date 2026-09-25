<?php

namespace Domain\Notification\Repositories\Contracts;

use Core\Http\Requests\TableRequest;
use Domain\Notification\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface INotificationRepository.
 */
interface INotificationRepository
{
    /**
     * Get the notifications pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the unread notifications
     */
    public function unread(): Collection;

    /**
     * Get the unread notifications
     */
    public function readAll(): array;

    /**
     * Get the notification.
     */
    public function show(Notification $notification): Notification;
}
