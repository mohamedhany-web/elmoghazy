<?php

namespace App\Http\Controllers\Student;

use App\Enums\ActivationStatus;
use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\Access\CodeIssuer;
use App\Services\Access\ContentGate;
use App\Services\Access\SubscriptionAccess;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function __construct(
        private readonly CodeIssuer $codes,
        private readonly ContentGate $gate,
        private readonly SubscriptionAccess $access,
    ) {}

    public function board(Request $request)
    {
        $student = $request->user();
        if ($student->isTeacher()) {
            abort(403);
        }

        $open = $this->access->openRows($student);
        $openCourseIds = $open->pluck('course_id');

        $courses = Course::query()
            ->where('grade_id', $student->grade_id)
            ->where('published', true)
            ->orderBy('sort')
            ->get(['id', 'title', 'description']);

        return response()->json([
            'student' => ['name' => $student->name, 'grade' => $student->grade?->title],
            'subscribed' => $open->contains(fn (Activation $activation) => ! $activation->lesson_id),
            'device_locked' => $this->access->deviceLocked($student),
            'activations' => $open->map(fn (Activation $activation) => [
                'course' => $activation->month?->title ?? $activation->course?->title ?? 'كل كورسات الصف',
                'ends_at' => $activation->ends_at,
                'status' => $activation->status === ActivationStatus::Active && $activation->ends_at?->isFuture() ? 'active' : 'locked',
            ]),
            'courses' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'open' => $openCourseIds->contains(null) || $openCourseIds->contains($course->id),
            ]),
        ]);
    }

    public function lessons(Request $request, Course $course)
    {
        if (! $course->published) {
            abort(404);
        }

        $lessons = $course->lessons()->where('published', true)->orderBy('sort')->get();

        return response()->json($lessons->map(function (Lesson $lesson) use ($request) {
            $decision = $this->gate->decide($request->user(), $lesson);

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'open' => $decision['allowed'],
                'message' => $decision['message'],
            ];
        }));
    }

    public function redeem(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $activation = $this->codes->redeem($request->user(), $data['code']);

        return response()->json([
            'ends_at' => $activation->ends_at,
            'course_id' => $activation->course_id,
            'lesson_id' => $activation->lesson_id,
        ]);
    }

    public function showLesson(Request $request, Lesson $lesson)
    {
        $decision = $this->gate->decide($request->user(), $lesson);
        if (! $decision['allowed']) {
            return $decision['status'] === 404
                ? response()->json(['message' => 'غير موجود.'], 404)
                : response()->json(['message' => $decision['message']], $decision['status']);
        }

        return response()->json([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'kind' => $lesson->kind,
            'body' => $lesson->kind === 'text' ? $lesson->body : null,
        ]);
    }

    public function completeLesson(Request $request, Lesson $lesson)
    {
        $decision = $this->gate->decide($request->user(), $lesson);
        if (! $decision['allowed']) {
            return response()->json(['message' => $decision['message'] ?? 'غير موجود.'], $decision['status']);
        }

        LessonProgress::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()],
        );

        return response()->json(['ok' => true]);
    }
}
