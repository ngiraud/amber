<?php

declare(strict_types=1);

namespace App\Casts;

use Native\Desktop\Facades\System;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;
use Throwable;

/**
 * Encrypts/decrypts settings values using NativePHP's OS Keychain (System::encrypt).
 *
 * Falls back to plain text when the NativePHP client is unavailable (dev/test).
 * If decryption fails (e.g. stale APP_KEY-encrypted value), returns null.
 */
class NativeEncryptCast implements SettingsCast
{
    public function get(mixed $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        try {
            $canEncrypt = System::canEncrypt();
        } catch (Throwable) {
            return $payload;
        }

        if (! $canEncrypt) {
            return $payload;
        }

        try {
            return System::decrypt($payload);
        } catch (Throwable) {
            return null;
        }
    }

    public function set(mixed $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        try {
            return System::canEncrypt() ? System::encrypt($payload) : $payload;
        } catch (Throwable) {
            return $payload;
        }
    }
}
