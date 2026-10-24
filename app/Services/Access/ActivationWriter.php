<?php

namespace App\Services\Access;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ActivationWriter
{
    public function open(
        User $student,
        ?int $courseId,
        ?int $lessonId,
        ?int $codeId,
        string $source,
        int $days,
        ?User $actor,
        ?int $studyMonthId = null,
        ?string $deviceHash = null,
    ): Activation {
        $start = Carbon::now();
        $activation = Activation::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'study_month_id' => $studyMonthId,
            'access_code_id' => $codeId,
            'source' => $source,
            'device_hash' => $deviceHash,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays(max(1, $days)),
            'status' => ActivationStatus::Active,
        ]);

        $this->log($actor, 'activation.opened', $activation, [
            'student_id' => $student->id,
            'source' => $source,
        ]);

        return $activation;
    }

    public function lock(Activation $activation, string $source, ?User $actor): Activation
    {
        if ($activation->status !== ActivationStatus::Locked) {
            $activation->update([
                'status' => ActivationStatus::Locked,
                'locked_at' => Carbon::now(),
                'lock_source' => $source,
            ]);
            $this->log($actor, 'activation.locked', $activation, ['lock_source' => $source]);
        }

        return $activation->refresh();
    }

    public function reopen(Activation $activation, int $days, ?User $actor): Activation
    {
        $end = $activation->ends_at->greaterThan(Carbon::now())
            ? $activation->ends_at->copy()
            : Carbon::now();

        $activation->update([
            'ends_at' => $end->addDays(max(1, $days)),
            'status' => ActivationStatus::Active,
            'locked_at' => null,
            'lock_source' => null,
        ]);

        $this->log($actor, 'activation.reopened', $activation, ['days' => $days]);

        return $activation->refresh();
    }

    public function extend(Activation $activation, int $days, User $actor): Activation
    {
        $end = $activation->ends_at->greaterThan(Carbon::now())
            ? $activation->ends_at->copy()
            : Carbon::now();

        $activation->ends_at = $end->addDays(max(1, $days));
        if ($activation->lock_source === 'auto') {
            $activation->status = ActivationStatus::Active;
            $activation->locked_at = null;
            $activation->lock_source = null;
        }
        $activation->save();

        $this->log($actor, 'activation.extended', $activation, ['days' => $days]);

        return $activation->refresh();
    }

    public function log(?User $actor, string $action, ?object $subject, array $meta = []): void
    {
        ActivityLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject->id ?? null,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
