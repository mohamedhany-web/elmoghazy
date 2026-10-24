<?php

namespace App\Services\Students;

use App\Enums\ActivationStatus;
use App\Models\AccessCode;
use App\Models\Activation;
use App\Models\ActivityLog;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\Reports\ReportSheet;
use Illuminate\Support\Carbon;

class StudentReport
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $student): array
    {
        $student->loadMissing('grade:id,title');
        \App\Services\Access\WatchMark::ensure($student);
        $activations = Activation::query()
            ->with(['course:id,title,grade_id', 'lesson:id,title'])
            ->where('user_id', $student->id)
            ->orderByDesc('ends_at')
            ->get();
        $codes = AccessCode::query()
            ->with(['course:id,title', 'lesson:id,title'])
            ->where('user_id', $student->id)
            ->latest()
            ->get();
        $progress = LessonProgress::query()
            ->with('lesson.course:id,title')
            ->where('user_id', $student->id)
            ->orderByDesc('completed_at')
            ->get();
        $attempts = ExamAttempt::query()
            ->with('exam:id,title')
            ->where('user_id', $student->id)
            ->orderByDesc('started_at')
            ->get();
        $requests = PurchaseRequest::query()
            ->with('month:id,title')
            ->where(function ($query) use ($student) {
                $query->where('user_id', $student->id);
                if ($student->phone) {
                    $query->orWhere('phone', $student->phone);
                }
            })
            ->latest()
            ->get();

        $lessonIds = $this->lessonIds($student, $activations);
        $done = $lessonIds === []
            ? 0
            : LessonProgress::query()
                ->where('user_id', $student->id)
                ->whereNotNull('completed_at')
                ->whereIn('lesson_id', $lessonIds)
                ->count();
        $total = count($lessonIds);
        $live = $activations->filter(fn (Activation $activation) => $this->isLive($activation))->count();

        $labels = ['course' => 'كورس', 'month' => 'شهر', 'lesson' => 'حصة', 'relock' => 'إعادة فتح', 'parent' => 'ولي أمر'];
        $progressPercent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        $sheet = ReportSheet::make(
            'تقرير الطالب',
            $student->name,
            ($student->grade?->title ?: 'من غير صف').' · '.($student->status?->value === 'disabled' ? 'الحساب متوقف' : 'الحساب نشط'),
            [
                ['label' => 'التليفون', 'value' => $student->phone ?: '—'],
                ['label' => 'ولي الأمر', 'value' => $student->parent_phone ?: '—'],
                ['label' => 'الصف', 'value' => $student->grade?->title ?: '—'],
                ['label' => 'كود الحماية', 'value' => $student->watch_mark ?: '—'],
                ['label' => 'اتسجل', 'value' => ReportSheet::date($student->created_at)],
            ],
            [
                ['label' => 'تفعيل ساري', 'value' => $live.' من '.$activations->count()],
                ['label' => 'الحصص', 'value' => $done.' خلصت · '.max(0, $total - $done).' فاضلة'],
                ['label' => 'الإنجاز', 'value' => $progressPercent.'٪'],
                ['label' => 'امتحانات', 'value' => (string) $attempts->count()],
            ],
            [
                [
                    'title' => 'التفعيلات',
                    'columns' => ['النطاق', 'المصدر', 'من', 'حتى', 'الحالة'],
                    'empty' => 'لسه مفيش تفعيلات.',
                    'rows' => $activations->map(fn (Activation $activation) => [
                        $activation->lesson?->title ?? $activation->course?->title ?? 'كل شهور الصف',
                        $activation->source === 'manual' ? 'يدوي' : 'كود',
                        ReportSheet::date($activation->starts_at),
                        ReportSheet::date($activation->ends_at),
                        $this->activationLabel($activation).($activation->lock_source ? ' · '.match ($activation->lock_source) {
                            'manual' => 'قفل يدوي',
                            'auto' => 'انتهت المدة',
                            default => '',
                        } : ''),
                    ])->all(),
                ],
                [
                    'title' => 'الحصص',
                    'columns' => ['الحصة', 'الكورس', 'اتخلصت'],
                    'empty' => 'لسه مفيش حصص اتفتحت.',
                    'rows' => $progress->map(fn (LessonProgress $row) => [
                        $row->lesson?->title ?? 'حصة اتشالت',
                        $row->lesson?->course?->title ?: '—',
                        ReportSheet::date($row->completed_at),
                    ])->all(),
                ],
                [
                    'title' => 'الامتحانات',
                    'columns' => ['الامتحان', 'الحالة', 'الدرجة', 'المئة', 'بدأ', 'اتسلم', 'تبديل التبويب'],
                    'empty' => 'لسه مفيش محاولات امتحان.',
                    'rows' => $attempts->map(fn (ExamAttempt $attempt) => [
                        $attempt->exam?->title ?? 'امتحان',
                        ($attempt->status === 'completed' ? 'اتسلم' : 'لسه شغال').($attempt->auto_submitted ? ' · تلقائي' : ''),
                        $attempt->score === null ? '—' : $attempt->score.' / '.($attempt->total ?? '—'),
                        $attempt->percentage === null ? '—' : $attempt->percentage.'٪',
                        ReportSheet::date($attempt->started_at),
                        ReportSheet::date($attempt->submitted_at),
                        (string) (int) $attempt->tab_switches,
                    ])->all(),
                ],
                [
                    'title' => 'الأكواد',
                    'columns' => ['النوع', 'آخر الكود', 'النطاق', 'الاستخدام', 'ينتهي', 'الحالة'],
                    'empty' => 'لسه مفيش أكواد مربوطة بالطالب.',
                    'rows' => $codes->map(function (AccessCode $code) use ($labels) {
                        $type = $code->type?->value ?? $code->type;

                        return [
                            $labels[$type] ?? (string) $type,
                            '••••'.($code->hint ?: ''),
                            $code->lesson?->title ?? $code->course?->title ?: '—',
                            $code->uses.' / '.$code->max_uses,
                            ReportSheet::date($code->expires_at),
                            $code->revoked_at ? 'ملغي' : 'متاح',
                        ];
                    })->all(),
                ],
                [
                    'title' => 'طلبات الشراء',
                    'columns' => ['الاسم', 'الموبايل', 'الشهر', 'الملاحظة', 'الحالة', 'التاريخ'],
                    'empty' => 'لسه مفيش طلبات.',
                    'rows' => $requests->map(fn (PurchaseRequest $request) => [
                        $request->name,
                        $request->phone ?: '—',
                        $request->month?->title ?: '—',
                        $request->note ?: '—',
                        $request->status === 'done' ? 'تم' : 'جديد',
                        ReportSheet::date($request->created_at),
                    ])->all(),
                ],
                [
                    'title' => 'السجل',
                    'columns' => ['العملية', 'مين', 'التفصيل', 'التاريخ'],
                    'empty' => 'لسه مفيش سجل.',
                    'rows' => $this->activity($student, $activations, $codes),
                ],
            ],
            $student->parent_phone,
        );
        $parts = preg_split('/\s+/u', trim((string) $student->name), 2) ?: [];
        $sheet['record'] = [
            'id' => $student->id,
            'first_name' => $student->first_name ?: ($parts[0] ?? ''),
            'second_name' => $student->second_name ?: ($parts[1] ?? ''),
            'phone' => $student->phone,
            'parent_phone' => $student->parent_phone,
            'grade_slug' => $student->grade?->slug,
            'status' => $student->status?->value ?? $student->status,
        ];

        return $sheet;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Activation>  $activations
     * @return list<int>
     */
    private function lessonIds(User $student, $activations): array
    {
        $gradeWide = $activations->contains(fn (Activation $activation) => $activation->course_id === null && $activation->lesson_id === null);
        $courseIds = $activations->pluck('course_id')->filter()->unique()->values();
        $lessonIds = $activations->pluck('lesson_id')->filter()->unique()->values();
        $query = Lesson::query()->where('published', true);

        if ($gradeWide && $student->grade_id) {
            return $query
                ->whereHas('course', fn ($course) => $course->where('grade_id', $student->grade_id)->where('published', true))
                ->pluck('id')
                ->all();
        }

        if ($courseIds->isEmpty() && $lessonIds->isEmpty()) {
            return [];
        }

        return $query->where(function ($inner) use ($courseIds, $lessonIds) {
            if ($courseIds->isNotEmpty()) {
                $inner->whereIn('course_id', $courseIds);
            }
            if ($lessonIds->isNotEmpty()) {
                $courseIds->isNotEmpty()
                    ? $inner->orWhereIn('id', $lessonIds)
                    : $inner->whereIn('id', $lessonIds);
            }
        })->pluck('id')->all();
    }

    private function isLive(Activation $activation): bool
    {
        return $activation->status === ActivationStatus::Active
            && $activation->starts_at
            && $activation->ends_at
            && $activation->starts_at->lessThanOrEqualTo(Carbon::now())
            && $activation->ends_at->greaterThan(Carbon::now());
    }

    private function activationLabel(Activation $activation): string
    {
        if ($this->isLive($activation)) {
            return 'ساري';
        }

        if ($activation->lock_source === 'manual' || $activation->status === ActivationStatus::Locked) {
            return 'مقفول';
        }

        return 'منتهي';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Activation>  $activations
     * @param  \Illuminate\Support\Collection<int, AccessCode>  $codes
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function activity(User $student, $activations, $codes)
    {
        $labels = [
            'student.created' => 'اتسجل الحساب',
            'student.disabled' => 'اتوقف الحساب',
            'student.enabled' => 'اتشغّل الحساب',
            'student.deleted' => 'اتحذف الحساب',
            'activation.opened' => 'اتفتح تفعيل',
            'activation.locked' => 'اتقفل التفعيل',
            'activation.reopened' => 'اتفتح التفعيل تاني',
            'activation.extended' => 'اتمدت المدة',
            'code.issued' => 'اتصدر كود',
            'code.redeemed' => 'اتستخدم كود',
            'code.revoked' => 'اتلغي كود',
        ];

        return ActivityLog::query()
            ->with('actor:id,name')
            ->where(function ($query) use ($student, $activations, $codes) {
                $query->where(fn ($row) => $row->where('subject_type', User::class)->where('subject_id', $student->id))
                    ->orWhere('actor_id', $student->id);
                if ($activations->isNotEmpty()) {
                    $query->orWhere(fn ($row) => $row->where('subject_type', Activation::class)->whereIn('subject_id', $activations->pluck('id')));
                }
                if ($codes->isNotEmpty()) {
                    $query->orWhere(fn ($row) => $row->where('subject_type', AccessCode::class)->whereIn('subject_id', $codes->pluck('id')));
                }
            })
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (ActivityLog $log) => [
                $labels[$log->action] ?? $log->action,
                $log->actor?->name ?: '—',
                isset($log->meta['days']) ? $log->meta['days'].' يوم' : '—',
                ReportSheet::date($log->created_at),
            ])
            ->all();
    }
}
