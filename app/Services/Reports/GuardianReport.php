<?php

namespace App\Services\Reports;

use App\Enums\ActivationStatus;
use App\Models\Activation;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class GuardianReport
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $student): array
    {
        $student->loadMissing('grade:id,title');
        $activations = Activation::query()
            ->with('course:id,title')
            ->where('user_id', $student->id)
            ->orderByDesc('ends_at')
            ->get();
        $courseIds = $activations->pluck('course_id')->filter()->unique();
        $lessons = $courseIds->isEmpty()
            ? 0
            : Lesson::query()->whereIn('course_id', $courseIds)->where('published', true)->count();
        $done = LessonProgress::query()
            ->where('user_id', $student->id)
            ->whereNotNull('completed_at')
            ->count();
        $progress = $lessons > 0 ? (int) round(($done / $lessons) * 100) : 0;

        return ReportSheet::make(
            'تقرير ولي الأمر',
            $student->name,
            $student->grade?->title ?: 'من غير صف',
            [
                ['label' => 'الاسم', 'value' => $student->name],
                ['label' => 'الصف', 'value' => $student->grade?->title ?: '—'],
            ],
            [
                ['label' => 'الإنجاز', 'value' => $progress.'٪'],
                ['label' => 'حصص خلصت', 'value' => (string) $done],
                ['label' => 'حصص في التفعيل', 'value' => (string) $lessons],
                ['label' => 'الفاضي', 'value' => (string) max(0, $lessons - $done)],
            ],
            [[
                'title' => 'التفعيلات',
                'columns' => ['النطاق', 'من', 'حتى', 'الحالة'],
                'empty' => 'لسه مفيش تفعيلات.',
                'rows' => $activations->map(function (Activation $activation) {
                    $live = $activation->status === ActivationStatus::Active
                        && $activation->ends_at
                        && $activation->ends_at->isFuture();

                    return [
                        $activation->course?->title ?: 'كل شهور الصف',
                        ReportSheet::date($activation->starts_at),
                        ReportSheet::date($activation->ends_at),
                        $live ? 'ساري' : 'مقفول',
                    ];
                })->all(),
            ]],
        );
    }
}
