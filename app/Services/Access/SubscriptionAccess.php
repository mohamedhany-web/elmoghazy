<?php

namespace App\Services\Access;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SubscriptionAccess
{
    public function __construct(
        private readonly ActivationWriter $activations,
        private readonly DeviceStamp $devices,
    ) {}

    /**
     * @return Collection<int, Activation>
     */
    public function openRows(User $student): Collection
    {
        $rows = Activation::query()->where('user_id', $student->id)->get();
        foreach ($rows as $activation) {
            if ($activation->status === ActivationStatus::Active && $activation->ends_at?->isPast()) {
                $this->activations->lock($activation, 'auto', null);
            }
            if ($activation->device_hash === DeviceStamp::REBIND) {
                $activation->forceFill(['device_hash' => $this->devices->hash(request())])->save();
            }
        }

        return Activation::query()
            ->with(['course:id,title', 'month:id,title'])
            ->where('user_id', $student->id)
            ->where('status', ActivationStatus::Active)
            ->where('starts_at', '<=', Carbon::now())
            ->where('ends_at', '>', Carbon::now())
            ->get()
            ->filter(fn (Activation $activation) => $this->devices->allows($activation, request()))
            ->values();
    }

    public function deviceLocked(User $student): bool
    {
        $active = Activation::query()
            ->where('user_id', $student->id)
            ->where('status', ActivationStatus::Active)
            ->where('starts_at', '<=', Carbon::now())
            ->where('ends_at', '>', Carbon::now())
            ->whereNotNull('device_hash')
            ->exists();

        return $active && $this->openRows($student)->isEmpty();
    }

    public function subscribed(User $student): bool
    {
        return $this->openRows($student)->contains(fn (Activation $activation) => ! $activation->lesson_id);
    }

    public function seesMonth(User $student, int $monthId): bool
    {
        $courseIds = null;

        return $this->openRows($student)->contains(function (Activation $activation) use ($student, $monthId, &$courseIds) {
            if ($activation->lesson_id) {
                return false;
            }

            if ($activation->study_month_id) {
                return (int) $activation->study_month_id === $monthId;
            }

            if ($activation->course_id) {
                $courseIds ??= Course::query()->where('grade_id', $student->grade_id)->pluck('id');

                return $courseIds->contains($activation->course_id);
            }

            return true;
        });
    }

    public function seesExam(User $student, ?int $monthId): bool
    {
        if ($monthId) {
            return $this->seesMonth($student, $monthId);
        }

        return $this->subscribed($student);
    }
}
