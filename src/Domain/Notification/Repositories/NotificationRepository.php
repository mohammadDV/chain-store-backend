<?php

namespace Domain\Notification\Repositories;

use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Notification\Models\Notification;
use Domain\Notification\Repositories\Contracts\INotificationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Class NotificationRepository.
 */
class NotificationRepository implements INotificationRepository
{
    use GlobalFunc;

    /**
     * Get the notifications pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        Notification::query()
            ->where('user_id', Auth::id())
            ->where('status', 1)
            ->where('read', 0)
            ->update([
                'read' => 1
            ]);

        $search = $request->get('query');
        return Notification::query()
            ->when(Auth::user()->level != 3, function ($query) {
                return $query->where('user_id', Auth::user()->id)
                    ->where('status', 1);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('title', 'content', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));
    }

    /**
     * Get the unread notification.
     * @return Collection
     */
    public function unread() :Collection
    {
        return Notification::query()
            ->where('user_id', Auth::id())
            ->where('status', 1)
            ->where('read', 0)
            ->limit(15)
            ->get();
    }

    /**
     * Get the unread notification.
     * @return array
     */
    public function readAll() :array
    {
        Notification::query()
            ->where('user_id', Auth::id())
            ->where('status', 1)
            ->where('read', 0)
            ->update([
                'read' => 1
            ]);

        return [
            'status' => 1,
            'message' => __('site.The operation has been successfully')
        ];
    }

    /**
     * Get the notification.
     * @param Notification $notification
     * @return Plan
     */
    public function show(Notification $notification) :Notification
    {

        $this->checkLevelAccess($notification->user_id == Auth::id());

        return $notification;
    }
}