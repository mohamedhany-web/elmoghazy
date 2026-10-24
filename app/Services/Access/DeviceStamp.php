<?php

namespace App\Services\Access;

use App\Models\Activation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DeviceStamp
{
    public const COOKIE = 'elmo_device';

    // المدرس صفّر الجهاز: أول جهاز يفتح بعدها يتربط بالتفعيل
    public const REBIND = 'rebind';

    public function token(Request $request): string
    {
        $current = $request->cookie(self::COOKIE);

        if (is_string($current) && preg_match('/^[a-f0-9]{64}$/', $current) === 1) {
            return $current;
        }

        $token = bin2hex(random_bytes(32));
        $request->cookies->set(self::COOKIE, $token);

        return $token;
    }

    public function hash(Request $request): string
    {
        return hash_hmac('sha256', $this->token($request), (string) config('app.key'));
    }

    public function allows(?Activation $activation, Request $request): bool
    {
        if (! $activation || ! $activation->device_hash || $activation->device_hash === self::REBIND) {
            return true;
        }

        $presented = $request->cookie(self::COOKIE);
        if (! is_string($presented) || preg_match('/^[a-f0-9]{64}$/', $presented) !== 1) {
            return false;
        }

        return hash_equals($activation->device_hash, hash_hmac('sha256', $presented, (string) config('app.key')));
    }

    public function cookie(Request $request): Cookie
    {
        return cookie(
            self::COOKIE,
            $this->token($request),
            60 * 24 * 400,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        );
    }

    public function attach(Request $request, Response $response): void
    {
        $response->headers->setCookie($this->cookie($request));
    }
}
