<?php

namespace App\Filament\Pages\Auth;

use Application\Api\User\Rules\Recaptcha;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Domain\AdminAccess\Services\AdminLoginAlertService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                Hidden::make('token'),
                ViewField::make('recaptcha_widget')
                    ->view('filament.auth.recaptcha', [
                        'siteKey' => config('services.recaptcha.site_key'),
                    ])
                    ->dehydrated(false),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $token = trim((string) ($this->data['token'] ?? ''));

        if ($token === '') {
            $siteKey = (string) config('services.recaptcha.site_key');

            $this->js(<<<JS
                (async () => {
                    const siteKey = {$this->jsString($siteKey)};
                    if (! window.grecaptcha?.execute) {
                        return;
                    }

                    await new Promise((resolve) => window.grecaptcha.ready(resolve));
                    const token = await window.grecaptcha.execute(siteKey, { action: 'ADMIN_LOGIN' });
                    \$wire.set('data.token', token);
                    \$wire.authenticate();
                })();
            JS);

            return null;
        }

        $validator = validator(
            ['token' => $token],
            ['token' => [new Recaptcha(expectedAction: 'ADMIN_LOGIN')]],
        );

        if ($validator->fails()) {
            $this->data['token'] = null;
            $this->consumeFailedAttempt();

            throw ValidationException::withMessages([
                'data.email' => $validator->errors()->first('token') ?: __('site.Invalid recaptcha'),
            ]);
        }

        $response = parent::authenticate();

        if ($response !== null) {
            $this->clearRateLimiter('authenticate');
        }

        return $response;
    }

    /**
     * Harden Filament's default 5/minute limiter for admin login.
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null): void
    {
        $maxAttempts = max(1, (int) config('admin.login.max_attempts', 3));
        $decaySeconds = max(1, (int) config('admin.login.decay_seconds', 900));
        $method ??= 'authenticate';
        $component ??= static::class;

        if ($this->isRateLimited($maxAttempts, $method, $component)) {
            $secondsUntilAvailable = RateLimiter::availableIn(
                $this->getRateLimitKey($method, $component)
            );

            app(AdminLoginAlertService::class)->rateLimited(
                email: isset($this->data['email']) ? (string) $this->data['email'] : null,
            );

            throw new TooManyRequestsException(
                $component,
                $method,
                request()->ip(),
                $secondsUntilAvailable,
            );
        }

        $this->hitRateLimiter($method, $decaySeconds, $component);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        parent::fireFailedEvent($guard, $user, $credentials);

        app(AdminLoginAlertService::class)->failedLogin(
            email: isset($credentials['email']) ? (string) $credentials['email'] : null,
            userId: $user?->getAuthIdentifier(),
        );
    }

    /**
     * Count invalid recaptcha submissions toward the same admin lockout bucket.
     */
    private function consumeFailedAttempt(): void
    {
        try {
            $this->rateLimit(
                (int) config('admin.login.max_attempts', 3),
                (int) config('admin.login.decay_seconds', 900),
            );
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::auth/pages/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => $exception->minutesUntilAvailable,
                ]),
            ]);
        }
    }

    private function jsString(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
