<?php

namespace App\Filament\Pages\Auth;

use Application\Api\User\Rules\Recaptcha;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

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

            throw ValidationException::withMessages([
                'data.email' => $validator->errors()->first('token') ?: __('site.Invalid recaptcha'),
            ]);
        }

        return parent::authenticate();
    }

    private function jsString(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
