<?php

namespace Domain\Setting\Services;

use Domain\Setting\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class InvoiceUnlockService
{
    public const TOKEN_TTL_SECONDS = 86400; // 24 hours

    private const TOKEN_PURPOSE = 'invoice_recipient';

    public function hasPassword(): bool
    {
        $hash = Setting::getInstance()->invoice_unlock_password;

        return is_string($hash) && $hash !== '';
    }

    public function verifyPassword(string $plain): bool
    {
        if ($plain === '' || ! $this->hasPassword()) {
            return false;
        }

        return Hash::check($plain, (string) Setting::getInstance()->invoice_unlock_password);
    }

    public function setPassword(string $plain): void
    {
        Setting::getInstance()->update([
            'invoice_unlock_password' => Hash::make($plain),
        ]);

        app(SettingService::class)->clearCache();
    }

    public function clearPassword(): void
    {
        Setting::getInstance()->update([
            'invoice_unlock_password' => null,
        ]);

        app(SettingService::class)->clearCache();
    }

    /**
     * @return array{token: string, expires_at: int}
     */
    public function issueToken(): array
    {
        $expiresAt = now()->addSeconds(self::TOKEN_TTL_SECONDS)->getTimestamp();

        $token = Crypt::encryptString(json_encode([
            'purpose' => self::TOKEN_PURPOSE,
            'exp' => $expiresAt,
        ], JSON_THROW_ON_ERROR));

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function verifyToken(?string $token): bool
    {
        return $this->readTokenExpiry($token) !== null;
    }

    /**
     * Return the token expiry unix timestamp when valid, otherwise null.
     */
    public function readTokenExpiry(?string $token): ?int
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        if (($payload['purpose'] ?? null) !== self::TOKEN_PURPOSE) {
            return null;
        }

        $expiresAt = (int) ($payload['exp'] ?? 0);

        if ($expiresAt < now()->getTimestamp()) {
            return null;
        }

        return $expiresAt;
    }
}
