<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\ActivationStatus;
use App\Enums\CodeType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\Activation;
use App\Models\CodeBatch;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\StudyMonth;
use App\Models\User;
use App\Services\Access\ActivationWriter;
use App\Services\Access\CodeIssuer;
use App\Services\Access\DeviceStamp;
use App\Services\Students\StudentReport;
use App\Support\EgyptianPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function __construct(
        private readonly ActivationWriter $activations,
        private readonly CodeIssuer $codes,
    ) {}

    public function students(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'disabled'])],
            'grade' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = User::query()
            ->where('role', UserRole::Student)
            ->with('grade:id,title,slug')
            ->orderBy('name');

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('parent_phone', 'like', $term)
                    ->orWhere('watch_mark', 'like', $term);
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['grade'])) {
            $query->whereHas('grade', fn ($grade) => $grade->where('title', $filters['grade']));
        }

        $map = fn (User $student) => [
            'id' => $student->id,
            'name' => $student->name,
            'first_name' => $student->first_name,
            'second_name' => $student->second_name,
            'phone' => $student->phone,
            'parent_phone' => $student->parent_phone,
            'watch_mark' => $student->watch_mark,
            'grade' => $student->grade?->title,
            'grade_slug' => $student->grade?->slug,
            'status' => $student->status?->value ?? $student->status,
        ];

        if (! $request->filled('page')) {
            return response()->json($query->get()->map($map));
        }

        $page = $query->paginate(25);

        return response()->json([
            'data' => $page->getCollection()->map($map)->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    public function courses()
    {
        $rows = Course::query()->with('grade:id,title,slug')->orderBy('sort')->get();
        $lessons = Lesson::query()->whereIn('course_id', $rows->pluck('id'))->orderBy('sort')->get()->groupBy('course_id');

        return response()->json($rows->map(function (Course $course) use ($lessons) {
            $items = $lessons->get($course->id, collect());

            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'grade' => $course->grade?->title,
                'grade_slug' => $course->grade?->slug,
                'published' => (bool) $course->published,
                'unlock_in_order' => (bool) $course->unlock_in_order,
                'lesson_count' => $items->count(),
                'lessons' => $items->map(fn (Lesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'kind' => $lesson->kind,
                    'body' => $lesson->body,
                    'published' => (bool) $lesson->published,
                ])->values(),
            ];
        }));
    }

    public function lessons(Course $course)
    {
        return response()->json($course->lessons()->orderBy('sort')->get()->map(fn (Lesson $lesson) => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'sort' => $lesson->sort,
            'published' => (bool) $lesson->published,
            'kind' => $lesson->kind,
        ]));
    }

    public function codes(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['open', 'used', 'expired', 'revoked'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = AccessCode::query()->with(['student:id,name', 'course:id,title', 'month:id,title'])->latest();

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($inner) use ($term) {
                $inner->where('hint', 'like', $term)
                    ->orWhereHas('student', fn ($student) => $student->where('name', 'like', $term))
                    ->orWhereHas('month', fn ($month) => $month->where('title', 'like', $term));
            });
        }

        $status = $filters['status'] ?? null;
        if ($status === 'revoked') {
            $query->whereNotNull('revoked_at');
        } elseif ($status === 'used') {
            $query->whereNull('revoked_at')->whereColumn('uses', '>=', 'max_uses');
        } elseif ($status === 'expired') {
            $query->whereNull('revoked_at')->whereColumn('uses', '<', 'max_uses')->whereNotNull('expires_at')->where('expires_at', '<=', now());
        } elseif ($status === 'open') {
            $query->whereNull('revoked_at')->whereColumn('uses', '<', 'max_uses')->where(function ($inner) {
                $inner->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
        }

        $map = fn (AccessCode $code) => [
            'id' => $code->id,
            'type' => $code->type?->value ?? $code->type,
            'hint' => $code->hint,
            'uses' => $code->uses,
            'max_uses' => $code->max_uses,
            'student' => $code->uses > 0 ? $code->student?->name : null,
            'student_id' => $code->uses > 0 ? $code->user_id : null,
            'course' => $code->course?->title,
            'month' => $code->month?->title,
            'duration_days' => $code->duration_days,
            'revoked' => (bool) $code->revoked_at,
            'expires_at' => $code->expires_at,
            'status' => $this->codeStatus($code),
        ];

        if (! $request->filled('page')) {
            return response()->json($query->limit(40)->get()->map($map));
        }

        $page = $query->paginate(25);

        return response()->json([
            'data' => $page->getCollection()->map($map)->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    public function activations(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'locked'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Activation::query()
            ->with(['student:id,name,grade_id', 'student.grade:id,title', 'course:id,title'])
            ->latest();

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($inner) use ($term) {
                $inner->whereHas('student', fn ($student) => $student->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                    ->orWhereHas('course', fn ($course) => $course->where('title', 'like', $term));
            });
        }
        if (($filters['status'] ?? null) === 'active') {
            $query->where('status', ActivationStatus::Active)->where('ends_at', '>', now());
        }
        if (($filters['status'] ?? null) === 'locked') {
            $query->where(function ($inner) {
                $inner->where('status', ActivationStatus::Locked)->orWhere('ends_at', '<=', now());
            });
        }

        $map = fn (Activation $activation) => [
            'id' => $activation->id,
            'student' => $activation->student?->name,
            'student_id' => $activation->user_id,
            'grade' => $activation->student?->grade?->title,
            'course' => $activation->course?->title ?? 'كل كورسات الصف',
            'source' => $activation->source,
            'ends_at' => $activation->ends_at,
            'status' => $activation->status === ActivationStatus::Active && $activation->ends_at?->isFuture() ? 'active' : 'locked',
        ];

        if (! $request->filled('page')) {
            return response()->json($query->limit(40)->get()->map($map));
        }

        $page = $query->paginate(25);

        return response()->json([
            'data' => $page->getCollection()->map($map)->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    public function storeStudent(Request $request)
    {
        $request->merge([
            'phone' => EgyptianPhone::normalize($request->input('phone')),
            'parent_phone' => EgyptianPhone::normalize($request->input('parent_phone')),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'second_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/', 'unique:users,phone'],
            'parent_phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
            'password' => ['required', 'string', 'min:8'],
            'grade' => ['required', 'string', 'exists:grades,slug'],
        ], [
            'phone.regex' => 'اكتب رقم موبايل مصري صحيح.',
            'phone.unique' => 'رقم التليفون مسجّل بالفعل.',
            'parent_phone.regex' => 'اكتب رقم موبايل مصري صحيح لولي الأمر.',
            'password.min' => 'كلمة المرور لازم تكون 8 حروف على الأقل.',
        ]);

        $student = User::query()->create([
            'first_name' => $data['first_name'],
            'second_name' => $data['second_name'],
            'name' => trim($data['first_name'].' '.$data['second_name']),
            'phone' => $data['phone'],
            'parent_phone' => $data['parent_phone'],
            'password' => $data['password'],
            'grade_id' => Grade::query()->where('slug', $data['grade'])->value('id'),
            'role' => UserRole::Student,
            'status' => AccountStatus::Active,
        ]);

        $this->activations->log($request->user(), 'student.created', $student);

        return response()->json(['id' => $student->id, 'name' => $student->name], 201);
    }

    public function studentReport(User $student, StudentReport $reports)
    {
        $this->assertStudent($student);

        return response()->json($reports->for($student));
    }

    public function updateStudent(Request $request, User $student)
    {
        $this->assertStudent($student);
        $request->merge([
            'phone' => $request->filled('phone') ? EgyptianPhone::normalize($request->input('phone')) : $request->input('phone'),
            'parent_phone' => $request->filled('parent_phone') ? EgyptianPhone::normalize($request->input('parent_phone')) : $request->input('parent_phone'),
        ]);
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:80'],
            'second_name' => ['sometimes', 'string', 'max:80'],
            'phone' => ['sometimes', 'regex:/^01[0125]\d{8}$/', Rule::unique('users', 'phone')->ignore($student->id)],
            'parent_phone' => ['sometimes', 'regex:/^01[0125]\d{8}$/'],
            'password' => ['nullable', 'string', 'min:8'],
            'grade' => ['sometimes', 'string', 'exists:grades,slug'],
        ], [
            'phone.regex' => 'اكتب رقم موبايل مصري صحيح.',
            'phone.unique' => 'رقم التليفون مسجّل بالفعل.',
            'parent_phone.regex' => 'اكتب رقم موبايل مصري صحيح لولي الأمر.',
            'password.min' => 'كلمة المرور لازم تكون 8 حروف على الأقل.',
        ]);

        if (isset($data['first_name'])) {
            $student->first_name = $data['first_name'];
        }
        if (isset($data['second_name'])) {
            $student->second_name = $data['second_name'];
        }
        if ($student->first_name || $student->second_name) {
            $student->name = trim(($student->first_name ?: '').' '.($student->second_name ?: ''));
        }
        if (isset($data['phone'])) {
            $student->phone = $data['phone'];
        }
        if (isset($data['parent_phone'])) {
            $student->parent_phone = $data['parent_phone'];
        }
        if (! empty($data['password'])) {
            $student->password = $data['password'];
        }
        if (isset($data['grade'])) {
            $student->grade_id = Grade::query()->where('slug', $data['grade'])->value('id');
        }
        $student->save();

        return response()->json(['ok' => true]);
    }

    public function destroyStudent(Request $request, User $student)
    {
        $this->assertStudent($student);
        $this->activations->log($request->user(), 'student.deleted', $student);
        $student->tokens()->delete();
        $student->delete();

        return response()->json(['ok' => true]);
    }

    public function disableStudent(Request $request, User $student)
    {
        $this->assertStudent($student);
        $student->update(['status' => AccountStatus::Disabled]);
        $student->tokens()->delete();
        $this->activations->log($request->user(), 'student.disabled', $student);

        return response()->json(['ok' => true]);
    }

    public function enableStudent(Request $request, User $student)
    {
        $this->assertStudent($student);
        $student->update(['status' => AccountStatus::Active]);
        $this->activations->log($request->user(), 'student.enabled', $student);

        return response()->json(['ok' => true]);
    }

    public function activateStudent(Request $request, User $student)
    {
        $this->assertStudent($student);
        $data = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:366'],
        ]);

        $activation = $this->activations->open(
            $student,
            $data['course_id'] ?? null,
            $data['lesson_id'] ?? null,
            null,
            'manual',
            (int) $data['duration_days'],
            $request->user(),
        );

        return response()->json(['id' => $activation->id, 'ends_at' => $activation->ends_at], 201);
    }

    public function lockStudent(Request $request, User $student)
    {
        $this->assertStudent($student);
        Activation::query()
            ->where('user_id', $student->id)
            ->where('status', ActivationStatus::Active)
            ->get()
            ->each(fn (Activation $activation) => $this->activations->lock($activation, 'manual', $request->user()));

        return response()->json(['ok' => true]);
    }

    public function resetDevice(Request $request, User $student)
    {
        $this->assertStudent($student);
        $count = Activation::query()
            ->where('user_id', $student->id)
            ->whereNotNull('device_hash')
            ->update(['device_hash' => DeviceStamp::REBIND]);
        $this->activations->log($request->user(), 'device.reset', $student, ['activations' => $count]);

        return response()->json(['ok' => true, 'activations' => $count]);
    }

    public function extendActivation(Request $request, Activation $activation)
    {
        $data = $request->validate([
            'duration_days' => ['required', 'integer', 'min:1', 'max:366'],
        ]);

        $updated = $this->activations->extend($activation, (int) $data['duration_days'], $request->user());

        return response()->json(['ends_at' => $updated->ends_at]);
    }

    public function storeCourse(Request $request)
    {
        $data = $this->courseData($request, true);
        $course = Course::query()->create([
            ...$data,
            'slug' => 'course-'.Str::lower(Str::random(8)),
            'lessons' => 0,
            'tag' => $data['tag'] ?? 'جديد',
            'mode' => 'recorded',
            'duration' => 'حسب التفعيل',
            'price' => 0,
            'published' => $data['published'] ?? false,
        ]);

        return response()->json(['id' => $course->id, 'slug' => $course->slug], 201);
    }

    public function updateCourse(Request $request, Course $course)
    {
        $course->update($this->courseData($request, false));

        return response()->json(['ok' => true]);
    }

    public function storeLesson(Request $request, Course $course)
    {
        $data = $this->lessonData($request);
        $lesson = $course->lessons()->create($data);
        $course->update(['lessons' => $course->lessons()->count()]);

        return response()->json(['id' => $lesson->id], 201);
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $lesson->update($this->lessonData($request));

        return response()->json(['ok' => true]);
    }

    public function destroyLesson(Lesson $lesson)
    {
        $course = $lesson->course;
        $lesson->delete();
        $course?->update(['lessons' => $course->lessons()->count()]);

        return response()->json(['ok' => true]);
    }

    public function destroyCourse(Course $course)
    {
        $course->delete();

        return response()->json(['ok' => true]);
    }

    public function storeCodes(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(CodeType::class)],
            'count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'study_month_id' => ['nullable', 'integer', 'exists:study_months,id'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:366'],
            'code_valid_days' => ['nullable', 'integer', 'min:1', 'max:366'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $type = CodeType::from($data['type']);
        if ($type === CodeType::Parent && empty($data['user_id'])) {
            return response()->json(['message' => 'كود ولي الأمر لازم يرتبط بطالب.'], 422);
        }
        if ($type === CodeType::Lesson && empty($data['lesson_id'])) {
            return response()->json(['message' => 'كود الحصة لازم يرتبط بحصة.'], 422);
        }
        if ($type === CodeType::Month && empty($data['study_month_id'])) {
            return response()->json(['message' => 'كود الشهر لازم يرتبط بشهر.'], 422);
        }
        if ($type === CodeType::Month && empty($data['duration_days'])) {
            return response()->json(['message' => 'حدد مدة ظهور الشهر بعد التفعيل.'], 422);
        }
        if ($type === CodeType::Month && empty($data['code_valid_days']) && empty($data['expires_at'])) {
            return response()->json(['message' => 'حدد مدة صلاحية الكود.'], 422);
        }

        if (! empty($data['code_valid_days'])) {
            $data['expires_at'] = now()->addDays((int) $data['code_valid_days']);
        }

        if (! empty($data['lesson_id']) && empty($data['course_id'])) {
            $data['course_id'] = Lesson::query()->whereKey($data['lesson_id'])->value('course_id');
        }

        $count = (int) ($data['count'] ?? 1);
        $label = $this->batchLabel($type, $data);

        $batch = DB::transaction(function () use ($request, $type, $data, $count, $label) {
            $batch = CodeBatch::query()->create([
                'issued_by' => $request->user()->id,
                'type' => $type,
                'study_month_id' => $type === CodeType::Month ? ($data['study_month_id'] ?? null) : null,
                'user_id' => $type === CodeType::Parent ? ($data['user_id'] ?? null) : null,
                'label' => $label,
                'code_valid_days' => isset($data['code_valid_days']) ? (int) $data['code_valid_days'] : null,
                'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
                'quantity' => $count,
            ]);

            $payload = $data;
            $payload['batch_id'] = $batch->id;
            for ($i = 0; $i < $count; $i++) {
                $this->codes->issue($request->user(), $type, $payload);
            }

            return $batch;
        });

        return response()->json([
            'batch_id' => $batch->id,
            'codes' => $this->batchPlainCodes($batch),
        ], 201);
    }

    public function codeBatches(Request $request)
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = CodeBatch::query()
            ->with(['month:id,title', 'student:id,name'])
            ->withCount([
                'codes as used_count' => fn ($query) => $query->whereColumn('uses', '>=', 'max_uses'),
                'codes as open_count' => fn ($query) => $query
                    ->whereNull('revoked_at')
                    ->whereColumn('uses', '<', 'max_uses')
                    ->where(function ($inner) {
                        $inner->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    }),
                'codes as revoked_count' => fn ($query) => $query->whereNotNull('revoked_at'),
            ])
            ->latest()
            ->paginate(20, ['*'], 'page', $filters['page'] ?? 1);

        $paginator->getCollection()->transform(function (CodeBatch $batch) {
            $printable = $batch->codes()->whereNotNull('plain_code')->exists();

            return [
                'id' => $batch->id,
                'type' => $batch->type?->value ?? $batch->type,
                'label' => $batch->label
                    ?: ($batch->month?->title ?: ($batch->student?->name ?: '—')),
                'quantity' => $batch->quantity,
                'used' => (int) $batch->used_count,
                'open' => (int) $batch->open_count,
                'revoked' => (int) $batch->revoked_count,
                'code_valid_days' => $batch->code_valid_days,
                'duration_days' => $batch->duration_days,
                'printable' => $printable,
                'created_at' => $batch->created_at?->toIso8601String(),
            ];
        });

        return response()->json($paginator);
    }

    public function codeBatch(CodeBatch $batch)
    {
        $batch->load(['month:id,title', 'student:id,name', 'codes.student:id,name']);

        $rows = $batch->codes()
            ->with('student:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (AccessCode $code) => [
                'id' => $code->id,
                'code' => $code->plain_code ?: ('••••'.($code->hint ?: '')),
                'printable' => filled($code->plain_code),
                'hint' => $code->hint,
                'status' => $this->codeStatus($code),
                'uses' => $code->uses,
                'max_uses' => $code->max_uses,
                'student' => $code->uses > 0 ? $code->student?->name : null,
                'expires_at' => $code->expires_at?->toIso8601String(),
            ]);

        $printable = $rows->contains(fn (array $row) => $row['printable']);

        return response()->json([
            'id' => $batch->id,
            'type' => $batch->type?->value ?? $batch->type,
            'label' => $batch->label
                ?: ($batch->month?->title ?: ($batch->student?->name ?: '—')),
            'quantity' => $batch->quantity,
            'code_valid_days' => $batch->code_valid_days,
            'duration_days' => $batch->duration_days,
            'created_at' => $batch->created_at?->toIso8601String(),
            'used' => $rows->where('status', 'used')->count(),
            'open' => $rows->where('status', 'open')->count(),
            'revoked' => $rows->where('status', 'revoked')->count(),
            'expired' => $rows->where('status', 'expired')->count(),
            'printable' => $printable,
            'codes' => $rows->values(),
        ]);
    }

    private function batchLabel(CodeType $type, array $data): string
    {
        if ($type === CodeType::Parent && ! empty($data['user_id'])) {
            return (string) (User::query()->whereKey($data['user_id'])->value('name') ?: 'ولي أمر');
        }
        if ($type === CodeType::Month && ! empty($data['study_month_id'])) {
            $month = StudyMonth::query()->with('grade:id,title')->find($data['study_month_id']);
            if ($month) {
                return $month->grade?->title
                    ? $month->title.' · '.$month->grade->title
                    : (string) $month->title;
            }
        }

        return 'دفعة أكواد';
    }

    /**
     * @return list<string>
     */
    private function batchPlainCodes(CodeBatch $batch): array
    {
        return $batch->codes()
            ->orderBy('id')
            ->get()
            ->map(fn (AccessCode $code) => (string) $code->plain_code)
            ->filter()
            ->values()
            ->all();
    }

    private function codeStatus(AccessCode $code): string
    {
        if ($code->revoked_at) {
            return 'revoked';
        }
        if ($code->uses >= $code->max_uses) {
            return 'used';
        }
        if ($code->expires_at && $code->expires_at->isPast()) {
            return 'expired';
        }

        return 'open';
    }

    public function revokeCode(Request $request, AccessCode $code)
    {
        $this->codes->revoke($code, $request->user());

        return response()->json(['ok' => true]);
    }

    public function destroyCodeBatch(Request $request, CodeBatch $batch)
    {
        DB::transaction(function () use ($batch, $request) {
            $batch->codes()
                ->whereNull('revoked_at')
                ->whereColumn('uses', '<', 'max_uses')
                ->get()
                ->each(fn (AccessCode $code) => $this->codes->revoke($code, $request->user()));

            $batch->codes()->delete();
            $batch->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function summary()
    {
        $active = Activation::query()
            ->where('status', ActivationStatus::Active)
            ->where('ends_at', '>', now())
            ->pluck('user_id')
            ->unique()
            ->count();

        $locked = Activation::query()
            ->where('status', ActivationStatus::Locked)
            ->pluck('user_id')
            ->unique()
            ->count();

        $used = (int) AccessCode::query()->sum('uses');
        $left = AccessCode::query()
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get()
            ->sum(fn (AccessCode $code) => max(0, $code->max_uses - $code->uses));

        return response()->json([
            'active_students' => $active,
            'locked_students' => $locked,
            'codes_used' => $used,
            'codes_left' => $left,
        ]);
    }

    private function courseData(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'grade' => [$creating ? 'required' : 'sometimes', 'string', 'exists:grades,slug'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => [$creating ? 'required' : 'sometimes', 'string'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'published' => ['nullable', 'boolean'],
            'unlock_in_order' => ['nullable', 'boolean'],
            'access_unit' => ['nullable', Rule::in(['days', 'weeks', 'months'])],
            'access_amount' => ['nullable', 'integer', 'min:1', 'max:36'],
            'access_starts_at' => ['nullable', 'date'],
            'access_ends_at' => ['nullable', 'date'],
        ]);

        if (isset($data['grade'])) {
            $data['grade_id'] = Grade::query()->where('slug', $data['grade'])->value('id');
            unset($data['grade']);
        }

        return $data;
    }

    private function lessonData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'published' => ['nullable', 'boolean'],
            'visible_from' => ['nullable', 'date'],
            'visible_until' => ['nullable', 'date'],
            'kind' => ['nullable', Rule::in(['text', 'video', 'file'])],
            'body' => ['nullable', 'string'],
        ]);
    }

    private function assertStudent(User $student): void
    {
        if ($student->isTeacher()) {
            abort(404);
        }
    }
}
