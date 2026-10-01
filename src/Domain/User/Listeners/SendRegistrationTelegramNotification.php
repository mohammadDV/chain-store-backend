<?php

namespace Domain\User\Listeners;

use Domain\User\Services\TelegramNotifier;
use Illuminate\Auth\Events\Registered;

class SendRegistrationTelegramNotification
{
    public function __construct(
        protected TelegramNotifier $notifier,
    ) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        $this->notifier->notifyOrder(
            'ثبت نام کاربر جدید'.PHP_EOL.
            'id '.($user->id ?? '-').PHP_EOL.
            'email '.($user->email ?? '-').PHP_EOL.
            'customer_number '.($user->customer_number ?? '-').PHP_EOL.
            'time '.now()
        );
    }
}
