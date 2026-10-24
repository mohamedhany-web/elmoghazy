<?php

namespace App\Services\Access;

use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Carbon;

class VideoGate
{
    public const DATE_LOCK = 'الفيديو اتقفل. راجع المدرس عشان تكمل.';

    public function __construct(private readonly SubscriptionAccess $access) {}

    /**
     * @return array{allowed: bool, status: int, message: ?string, locked: bool}
     */
    public function decide(User $student, Video $video): array
    {
        $video->loadMissing('unit.month');
        $month = $video->unit?->month;

        if (! $student->isActive()) {
            return $this->deny(403, 'الحساب متوقف. راجع المدرس.', true);
        }

        if (! $month || ! $video->published || ! $video->unit->published || ! $month->published) {
            return $this->deny(404, null, false);
        }

        if ((int) $month->grade_id !== (int) $student->grade_id) {
            return $this->deny(404, null, false);
        }

        if (! $this->access->seesMonth($student, (int) $month->id)) {
            return $this->deny(404, null, false);
        }

        if ($this->dateLocked($video)) {
            return $this->deny(403, self::DATE_LOCK, true);
        }

        return ['allowed' => true, 'status' => 200, 'message' => null, 'locked' => false];
    }

    public function assertWatch(User $student, Video $video): void
    {
        $decision = $this->decide($student, $video);

        if (! $decision['allowed']) {
            abort($decision['status'], $decision['message'] ?? '');
        }
    }

    public function dateLocked(Video $video): bool
    {
        $video->loadMissing('unit.month');
        $at = $video->locks_at ?? $video->unit?->locks_at ?? $video->unit?->month?->locks_at;

        return $at instanceof Carbon && $at->lessThanOrEqualTo(Carbon::now());
    }

    public function locksAt(Video $video): ?Carbon
    {
        $video->loadMissing('unit.month');

        return $video->locks_at ?? $video->unit?->locks_at ?? $video->unit?->month?->locks_at;
    }

    /**
     * @return array{allowed: bool, status: int, message: ?string, locked: bool}
     */
    private function deny(int $status, ?string $message, bool $locked): array
    {
        return ['allowed' => false, 'status' => $status, 'message' => $message, 'locked' => $locked];
    }
}
