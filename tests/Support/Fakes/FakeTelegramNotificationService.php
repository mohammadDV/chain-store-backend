<?php

namespace Tests\Support\Fakes;

use Domain\User\Services\TelegramNotificationService;

/**
 * No-op Telegram service for deterministic tests.
 */
class FakeTelegramNotificationService extends TelegramNotificationService
{
    /** @var list<string> */
    public array $messages = [];

    public function __construct()
    {
        // Skip parent bot client construction.
    }

    public function sendNotification($chatId, $message)
    {
        $this->messages[] = (string) $message;

        return null;
    }
}
