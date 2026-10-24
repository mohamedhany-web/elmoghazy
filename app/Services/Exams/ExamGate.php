<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\User;
use App\Services\Access\ContentGate;
use App\Services\Access\SubscriptionAccess;
use Illuminate\Support\Carbon;

class ExamGate
{
    public const CLOSED = 'الامتحان اتقفل. راجع المدرس عشان تكمل.';

    public function __construct(private readonly SubscriptionAccess $access) {}

    /**
     * @return array{allowed: bool, status: int, message: ?string, locked: bool}
     */
    public function decide(User $student, Exam $exam): array
    {
        if (! $student->isActive()) {
            return $this->deny(403, 'الحساب متوقف. راجع المدرس.', true);
        }

        if (! $exam->published || ! $exam->active || (int) $exam->grade_id !== (int) $student->grade_id) {
            return $this->deny(404, null, false);
        }

        if (! $this->access->seesExam($student, $exam->study_month_id ? (int) $exam->study_month_id : null)) {
            return $this->deny(403, ContentGate::LOCK_MESSAGE, true);
        }

        if ($exam->opens_at instanceof Carbon && $exam->opens_at->isFuture()) {
            return $this->deny(403, 'الامتحان لسه ما تفتحش.', true);
        }

        if (! $exam->permanent && $exam->closes_at instanceof Carbon && $exam->closes_at->lessThanOrEqualTo(Carbon::now())) {
            return $this->deny(403, self::CLOSED, true);
        }

        return ['allowed' => true, 'status' => 200, 'message' => null, 'locked' => false];
    }

    public function assertOpen(User $student, Exam $exam): void
    {
        $decision = $this->decide($student, $exam);
        if (! $decision['allowed']) {
            abort($decision['status'], $decision['message'] ?? '');
        }
    }

    /**
     * @return array{allowed: bool, status: int, message: ?string, locked: bool}
     */
    private function deny(int $status, ?string $message, bool $locked): array
    {
        return ['allowed' => false, 'status' => $status, 'message' => $message, 'locked' => $locked];
    }
}
