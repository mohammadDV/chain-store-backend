<?php

namespace Domain\User\Jobs;

use Domain\User\Services\TelegramNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 180];

    public const MAX_MESSAGE_LENGTH = 3500;

    public function __construct(
        public readonly string $chatId,
        public readonly string $message,
    ) {}

    public function handle(TelegramNotificationService $telegram): void
    {
        try {
            $telegram->sendNotification(
                $this->chatId,
                self::truncate($this->message),
            );
        } catch (Throwable $e) {
            Log::warning('Telegram send failed', [
                'chat_id' => $this->chatId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Retry without rethrowing — avoids reportable → telegram feedback loops.
            if ($this->attempts() < $this->tries) {
                $delay = $this->backoff[$this->attempts() - 1] ?? 60;
                $this->release($delay);

                return;
            }

            Log::error('SendTelegramMessageJob exhausted retries', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function truncate(string $message): string
    {
        if (mb_strlen($message) <= self::MAX_MESSAGE_LENGTH) {
            return $message;
        }

        return mb_substr($message, 0, self::MAX_MESSAGE_LENGTH - 20)."\n…(truncated)";
    }
}
