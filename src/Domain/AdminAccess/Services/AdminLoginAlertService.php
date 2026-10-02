<?php

namespace Domain\AdminAccess\Services;

use Domain\User\Services\TelegramNotifier;
use Illuminate\Support\Facades\Cache;

class AdminLoginAlertService
{
    public function __construct(
        protected TelegramNotifier $notifier,
    ) {}

    public function failedLogin(?string $email, mixed $userId = null): void
    {
        $this->notifier->notifySecurity($this->buildMessage(
            title: 'Admin login failed',
            email: $email,
            userId: $userId,
        ));
    }

    public function rateLimited(?string $email = null): void
    {
        $ip = (string) (request()->ip() ?? 'unknown');
        $decay = max(1, (int) config('admin.login.decay_seconds', 900));
        $cacheKey = 'admin-login-rate-alert:'.$ip;

        // One Telegram alert per IP for the duration of the lockout window.
        if (! Cache::add($cacheKey, true, $decay)) {
            return;
        }

        $this->notifier->notifySecurity($this->buildMessage(
            title: 'Admin login rate limited',
            email: $email,
        ));
    }

    private function buildMessage(string $title, ?string $email = null, mixed $userId = null): string
    {
        $safeEmail = $this->safeEmail($email);

        return implode(PHP_EOL, [
            $title,
            'email: '.$safeEmail,
            'user_id: '.($userId ?? '-'),
            'ip: '.(request()->ip() ?? '-'),
            'user_agent: '.$this->safeUserAgent(),
            'time: '.now()->toDateTimeString(),
        ]);
    }

    private function safeEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '-';
        }

        return $email;
    }

    private function safeUserAgent(): string
    {
        $agent = (string) (request()->userAgent() ?? '-');

        if (mb_strlen($agent) > 120) {
            return mb_substr($agent, 0, 117).'...';
        }

        return $agent !== '' ? $agent : '-';
    }
}
