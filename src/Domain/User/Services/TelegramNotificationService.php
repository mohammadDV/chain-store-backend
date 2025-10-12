<?php

namespace Domain\User\Services;

use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class TelegramNotificationService
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    public function sendNotification($chatId, $message)
    {
        return $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'    => $message,
        ]);
    }

    // Method to send photo with caption
    public function sendPhoto($chatId, $photoPathOrUrl, $caption = null)
    {
        $photo = InputFile::create($photoPathOrUrl);

        return $this->telegram->sendPhoto([
            'chat_id' => $chatId,
            'photo'   => $photo,
            'caption' => $caption
        ]);
    }
}
