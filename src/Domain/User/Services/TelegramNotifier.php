<?php

namespace Domain\User\Services;

use Domain\User\Jobs\SendTelegramMessageJob;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispatches Telegram alerts onto the low-priority queue.
 * Never performs HTTP in the request path.
 */
class TelegramNotifier
{
    public function notifyOrder(string $message): void
    {
        $this->dispatch(
            (string) config('telegram.channels.order', ''),
            $message,
        );
    }

    public function notifyError(string $message): void
    {
        $this->dispatch(
            (string) config('telegram.channels.error', ''),
            $message,
        );
    }

    private function dispatch(string $chatId, string $message): void
    {
        if (! config('telegram.enabled', true)) {
            return;
        }

        if ($chatId === '' || trim($message) === '') {
            return;
        }

        try {
            $queue = (string) config('telegram.queue', 'low');

            SendTelegramMessageJob::dispatch($chatId, $message)
                ->onQueue($queue)
                ->afterCommit();
        } catch (Throwable $e) {
            Log::warning('Failed to dispatch Telegram notification', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
