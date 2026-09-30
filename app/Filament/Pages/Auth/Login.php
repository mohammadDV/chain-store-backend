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
                Hidden::make('token')
                    ->required()
                    ->validationAttribute(__('site.Invalid recaptcha')),
                ViewField::make('recaptcha_widget')
                    ->view('filament.auth.recaptcha')
                    ->dehydrated(false),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $token = $this->data['token'] ?? null;

        $validator = validator(
            ['token' => $token],
            ['token' => [new Recaptcha]],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'data.token' => $validator->errors()->first('token') ?: __('site.Invalid recaptcha'),
            ]);
        }

        return parent::authenticate();
    }
}
