<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Support\Str;

class WatchMark
{
    public static function mint(): string
    {
        do {
            $mark = 'MG-'.strtoupper(Str::random(8));
        } while (User::query()->where('watch_mark', $mark)->exists());

        return $mark;
    }

    public static function ensure(User $user): string
    {
        if (is_string($user->watch_mark) && $user->watch_mark !== '') {
            return $user->watch_mark;
        }

        $mark = self::mint();
        $user->forceFill(['watch_mark' => $mark])->save();

        return $mark;
    }
}
