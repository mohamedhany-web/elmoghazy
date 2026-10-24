<?php

namespace App\Services\Access;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Carbon;

class ContentGate
{
    public const LOCK_MESSAGE = 'التفعيل منتهٍ. راجع المدرس عشان تكمل.';

    public function __construct(private readonly ActivationWriter $activations) {}

    /**
     * @return array{allowed: bool, status: int, message: ?string}
     */
    public function decide(User $student, Lesson $lesson): array
    {
        $lesson->loadMissing('course');

        if (! $student->isActive()) {
            return $this->deny(403, 'الحساب متوقف. راجع المدرس.');
        }

        if (! $lesson->published || ! $lesson->course?->published) {
            return $this->deny(404, null);
        }

        $now = Carbon::now();
        if (($lesson->visible_from && $lesson->visible_from->greaterThan($now))
            || ($lesson->visible_until && $lesson->visible_until->lessThan($now))) {
            return $this->hasPass($student, $lesson)
                ? $this->deny(403, 'الحصة لسه موعدها مجاش.')
                : $this->deny(404, null);
        }

        $pass = $this->freshPass($student, $lesson);
        if (! $pass) {
            return $this->hadAccess($student, $lesson)
                ? $this->deny(403, self::LOCK_MESSAGE)
                : $this->deny(404, null);
        }

        if ($lesson->course->unlock_in_order && ! $this->previousCompleted($student, $lesson)) {
            return $this->deny(403, 'الحصة دي تتفتح بعد ما تخلّص اللي قبلها.');
        }

        return ['allowed' => true, 'status' => 200, 'message' => null];
    }

    private function freshPass(User $student, Lesson $lesson): ?Activation
    {
        $match = $this->candidates($student, $lesson)->first(function (Activation $activation) {
            if ($activation->status === ActivationStatus::Active && $activation->ends_at->isPast()) {
                $this->activations->lock($activation, 'auto', null);
            }

            return $activation->status === ActivationStatus::Active
                && $activation->starts_at->lessThanOrEqualTo(Carbon::now())
                && $activation->ends_at->greaterThan(Carbon::now());
        });

        return $match;
    }

    private function hasPass(User $student, Lesson $lesson): bool
    {
        return $this->freshPass($student, $lesson) !== null;
    }

    private function hadAccess(User $student, Lesson $lesson): bool
    {
        return $this->candidates($student, $lesson)->isNotEmpty();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Activation>
     */
    private function candidates(User $student, Lesson $lesson)
    {
        return Activation::query()
            ->where('user_id', $student->id)
            ->where(function ($query) use ($lesson) {
                $query->where('lesson_id', $lesson->id)
                    ->orWhere(function ($course) use ($lesson) {
                        $course->whereNull('lesson_id')->where('course_id', $lesson->course_id);
                    })
                    ->orWhere(function ($grade) {
                        $grade->whereNull('lesson_id')->whereNull('course_id');
                    });
            })
            ->orderByDesc('ends_at')
            ->get()
            ->filter(function (Activation $activation) use ($student, $lesson) {
                if ($activation->lesson_id) {
                    return (int) $activation->lesson_id === (int) $lesson->id;
                }

                if ($activation->course_id) {
                    return (int) $activation->course_id === (int) $lesson->course_id;
                }

                return $student->grade_id
                    && (int) $lesson->course?->grade_id === (int) $student->grade_id;
            })
            ->values();
    }

    private function previousCompleted(User $student, Lesson $lesson): bool
    {
        $previous = Lesson::query()
            ->where('course_id', $lesson->course_id)
            ->where('published', true)
            ->where('sort', '<', $lesson->sort)
            ->orderByDesc('sort')
            ->first();

        if (! $previous) {
            return true;
        }

        return LessonProgress::query()
            ->where('user_id', $student->id)
            ->where('lesson_id', $previous->id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    /**
     * @return array{allowed: bool, status: int, message: ?string}
     */
    private function deny(int $status, ?string $message): array
    {
        return ['allowed' => false, 'status' => $status, 'message' => $message];
    }
}
