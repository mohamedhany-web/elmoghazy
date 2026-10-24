<?php

namespace App\Services\Access;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StreamTicket
{
    public function issue(int $userId, int $videoId): string
    {
        $token = Str::random(48);
        Cache::put($this->key($token), [
            'user_id' => $userId,
            'video_id' => $videoId,
        ], now()->addMinutes(max(10, (int) config('media.stream_minutes'))));

        return $token;
    }

    public function allows(string $token, int $userId, int $videoId): bool
    {
        $row = $this->read($token);
        if ($row === null) {
            return false;
        }

        return (int) ($row['user_id'] ?? 0) === $userId
            && (int) ($row['video_id'] ?? 0) === $videoId;
    }

    /**
     * @return array{user_id: int, video_id: int}|null
     */
    public function read(string $token): ?array
    {
        if ($token === '' || preg_match('/^[A-Za-z0-9]{48}$/', $token) !== 1) {
            return null;
        }

        $row = Cache::get($this->key($token));
        if (! is_array($row)) {
            return null;
        }

        return [
            'user_id' => (int) ($row['user_id'] ?? 0),
            'video_id' => (int) ($row['video_id'] ?? 0),
        ];
    }

    private function key(string $token): string
    {
        return 'vstream:'.$token;
    }
}
