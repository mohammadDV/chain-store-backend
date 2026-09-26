<?php

namespace Domain\Notification\Services;

use Application\Api\User\Notifications\EmailNotification;
use Domain\Notification\Models\Notification;
use Domain\User\Models\User;

class NotificationService
{
    const PROFILE = 'profile';

    const CHAT = 'chat';

    const TICKET = 'ticket';

    const PRODUCT = 'product';

    const ORDER = 'order';

    const REVIEW = 'review';

    const WALLET = 'wallet';

    const WITHDRAWAL = 'withdrawal';

    /**
     * Create and send notification
     */
    public static function create(array $info, User $user, bool $hasEmail = true): void
    {
        Notification::create([
            'title' => $info['title'],
            'content' => $info['content'],
            'status' => 1,
            'user_id' => $user->id,
            'model_id' => $info['id'] ?? null,
            'model_type' => $info['type'] ?? NotificationService::PROFILE,
        ]);

        if ($hasEmail) {
            $actionUrl = $info['action_url'] ?? null;

            // Persist DB row first; only queue the email after the surrounding DB transaction commits.
            $user->notify(
                (new EmailNotification($info['title'], $info['content'], $actionUrl))->afterCommit()
            );
        }
    }
}
