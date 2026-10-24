<?php

namespace App\Services\Access;

use App\Enums\CodeType;
use App\Models\AccessCode;
use App\Models\Activation;
use App\Models\Course;
use App\Models\StudyMonth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CodeIssuer
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    // رسالة واضحة لكل سبب بدل "الكود غير صالح" لكل الحالات
    private const REASONS = [
        'missing' => 'الكود غير صحيح. راجع الحروف والأرقام وجرب تاني.',
        'revoked' => 'الكود ده اتلغى. راجع المدرس.',
        'used' => 'الكود ده اتستخدم قبل كده على حساب تاني.',
        'mine' => 'الكود ده متفعّل على حسابك بالفعل. لو الشهر مش ظاهر، افتح من نفس الجهاز والمتصفح اللي فعّلت منه أو راجع المدرس.',
        'expired' => 'صلاحية الكود انتهت. راجع المدرس.',
        'grade' => 'الكود ده لشهر في صف دراسي غير صفك المسجّل. راجع المدرس.',
    ];

    // أرقام عربية/فارسية → إنجليزي
    private const DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public function __construct(
        private readonly ActivationWriter $activations,
        private readonly DeviceStamp $devices,
    ) {}

    public function issue(User $teacher, CodeType $type, array $input): array
    {
        $plain = self::plain();
        $days = isset($input['duration_days']) ? (int) $input['duration_days'] : null;

        $code = AccessCode::query()->create([
            'issued_by' => $teacher->id,
            'batch_id' => $input['batch_id'] ?? null,
            'user_id' => $type === CodeType::Parent ? ($input['user_id'] ?? null) : null,
            'course_id' => $input['course_id'] ?? null,
            'lesson_id' => $input['lesson_id'] ?? null,
            'study_month_id' => $type === CodeType::Month ? ($input['study_month_id'] ?? null) : null,
            'type' => $type,
            'code_hash' => self::hash($plain),
            'hint' => substr($plain, -4),
            'plain_code' => $plain,
            'duration_days' => $days,
            'max_uses' => 1,
            'expires_at' => $input['expires_at'] ?? null,
        ]);

        $this->activations->log($teacher, 'code.issued', $code, ['type' => $type->value]);

        return ['code' => $plain, 'record' => $code];
    }

    public function redeem(User $student, string $plain): Activation
    {
        if (! $student->isActive() || $student->isTeacher()) {
            throw ValidationException::withMessages([
                'code' => ['الكود غير صالح.'],
            ]);
        }

        $result = DB::transaction(function () use ($student, $plain) {
            $code = AccessCode::query()->where('code_hash', self::hash($plain))->lockForUpdate()->first();

            $problem = $this->problem($code, $student);
            if ($problem !== null) {
                return [$problem, $code];
            }

            $device = $this->devices->hash(request());
            $activation = $this->apply($student, $code, $device);
            $code->forceFill([
                'uses' => $code->uses + 1,
                'user_id' => $code->user_id ?: $student->id,
            ])->save();
            $this->activations->log($student, 'code.redeemed', $code, ['student_id' => $student->id]);

            return $activation;
        });

        if ($result instanceof Activation) {
            return $result;
        }

        [$problem, $code] = $result;
        // يتسجل برا الـ transaction عشان المدرس يشوف سبب الرفض في السجل
        $this->activations->log($student, 'code.rejected', $code, ['reason' => $problem]);

        throw ValidationException::withMessages([
            'code' => [self::REASONS[$problem]],
        ]);
    }

    public function revoke(AccessCode $code, User $teacher): void
    {
        $code->update(['revoked_at' => now()]);
        Activation::query()
            ->where('access_code_id', $code->id)
            ->where('status', \App\Enums\ActivationStatus::Active)
            ->get()
            ->each(fn (Activation $activation) => $this->activations->lock($activation, 'manual', $teacher));
        $this->activations->log($teacher, 'code.revoked', $code);
    }

    public function parentSubject(string $plain): User
    {
        $code = AccessCode::query()->where('code_hash', self::hash($plain))->first();

        if (! $code
            || $code->type !== CodeType::Parent
            || ! $code->user_id
            || $code->revoked_at
            || ($code->expires_at && $code->expires_at->isPast())
            || $code->uses >= $code->max_uses
        ) {
            throw ValidationException::withMessages([
                'code' => ['الكود غير صالح.'],
            ]);
        }

        return $code->student()->with('grade')->firstOrFail();
    }

    public static function hash(string $plain): string
    {
        return hash_hmac('sha256', self::normalize($plain), (string) config('app.key'));
    }

    public static function normalize(string $plain): string
    {
        $plain = strtr($plain, self::DIGITS);
        $raw = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $plain));
        if (strlen($raw) === 12) {
            return substr($raw, 0, 4).'-'.substr($raw, 4, 4).'-'.substr($raw, 8, 4);
        }
        if (strlen($raw) === 8) {
            return substr($raw, 0, 4).'-'.substr($raw, 4, 4);
        }

        return strtoupper(trim($plain));
    }

    public static function plain(): string
    {
        $chars = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < 12; $i++) {
            $chars .= self::ALPHABET[random_int(0, $max)];
        }

        return substr($chars, 0, 4).'-'.substr($chars, 4, 4).'-'.substr($chars, 8, 4);
    }

    private function problem(?AccessCode $code, User $student): ?string
    {
        if (! $code || $code->type === CodeType::Parent) {
            return 'missing';
        }

        if ($code->revoked_at) {
            return 'revoked';
        }

        if ($code->uses >= $code->max_uses) {
            return (int) $code->user_id === (int) $student->id ? 'mine' : 'used';
        }

        if ($code->user_id && (int) $code->user_id !== (int) $student->id) {
            return 'used';
        }

        if ($code->expires_at && $code->expires_at->isPast()) {
            return 'expired';
        }

        if ($code->type === CodeType::Month) {
            $month = $code->study_month_id ? StudyMonth::query()->find($code->study_month_id) : null;

            if (! $month) {
                return 'missing';
            }

            if ((int) $month->grade_id !== (int) $student->grade_id) {
                return 'grade';
            }
        }

        return null;
    }

    private function apply(User $student, AccessCode $code, string $deviceHash): Activation
    {
        $days = $this->daysFor($code);

        if ($code->type === CodeType::Relock) {
            $existing = Activation::query()
                ->where('user_id', $student->id)
                ->when($code->course_id, fn ($query) => $query->where('course_id', $code->course_id))
                ->latest('ends_at')
                ->first();

            if ($existing) {
                return $this->activations->reopen($existing, $days, $student);
            }
        }

        return $this->activations->open(
            $student,
            $code->type === CodeType::Month ? null : $code->course_id,
            $code->type === CodeType::Lesson ? $code->lesson_id : null,
            $code->id,
            'code',
            $days,
            $student,
            $code->type === CodeType::Month ? $code->study_month_id : null,
            $deviceHash,
        );
    }

    private function daysFor(AccessCode $code): int
    {
        if ($code->duration_days) {
            return $code->duration_days;
        }

        $course = $code->course_id ? Course::query()->find($code->course_id) : null;
        if (! $course) {
            return 30;
        }

        return match ($course->access_unit) {
            'days' => (int) $course->access_amount,
            'weeks' => (int) $course->access_amount * 7,
            'months' => (int) $course->access_amount * 30,
            default => 30,
        } ?: 30;
    }
}
