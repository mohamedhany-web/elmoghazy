<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Grade;
use App\Services\Exams\ExamWriter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function __construct(private readonly ExamWriter $exams) {}

    public function index()
    {
        $rows = Exam::query()->with('grade')->withCount('questions')->orderByDesc('id')->get();

        return response()->json($rows->map(fn (Exam $exam) => [
            'id' => $exam->id,
            'title' => $exam->title,
            'grade_id' => $exam->grade_id,
            'grade' => $exam->grade?->title,
            'published' => $exam->published,
            'permanent' => $exam->permanent,
            'duration_minutes' => $exam->duration_minutes,
            'question_count' => $exam->questions_count,
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->fields($request, true);
        $grade = Grade::query()->find($data['grade_id']);
        if (! $grade) {
            throw ValidationException::withMessages([
                'grade_id' => ['اختار السنة الدراسية.'],
            ]);
        }

        $exam = $this->exams->create($grade, $data);

        return response()->json(['id' => $exam->id], 201);
    }

    public function show(Exam $exam)
    {
        return response()->json($this->exams->detail($exam));
    }

    public function update(Request $request, Exam $exam)
    {
        $data = $this->fields($request, false);
        unset($data['grade_id']);
        $this->exams->update($exam, $data);

        return response()->json(['ok' => true]);
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();

        return response()->json(['ok' => true]);
    }

    public function syncQuestions(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'questions' => ['present', 'array'],
            'questions.*.id' => ['required', 'integer'],
            'questions.*.points' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
        ], [
            'questions.*.id.required' => 'اختار السؤال.',
        ]);

        $this->exams->syncQuestions($exam, array_map(fn (array $row) => [
            'id' => (int) $row['id'],
            'points' => (float) ($row['points'] ?? 1),
        ], $data['questions']));

        return response()->json(['ok' => true]);
    }

    public function generate(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'unit' => ['nullable', 'integer'],
            'lesson' => ['nullable', 'integer'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'count.required' => 'اكتب عدد الأسئلة.',
        ]);

        return response()->json(['count' => $this->exams->generate($exam, $data)]);
    }

    public function attempts(Exam $exam)
    {
        return response()->json($this->exams->attempts($exam));
    }

    public function grade(Request $request, ExamAnswer $answer)
    {
        $data = $request->validate([
            'awarded' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ], [
            'awarded.required' => 'اكتب الدرجة.',
        ]);
        $this->exams->grade($answer, (float) $data['awarded'], $data['feedback'] ?? null);

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fields(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'grade_id' => [$creating ? 'required' : 'prohibited', 'integer'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'permanent' => ['sometimes', 'boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'pass_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'closes_at' => ['nullable', 'date'],
            'opens_at' => ['nullable', 'date'],
            'study_month_id' => ['nullable', 'integer'],
            'attempts_allowed' => ['nullable', 'integer', 'min:0', 'max:10'],
            'published' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_choices' => ['sometimes', 'boolean'],
            'explanation_when' => ['sometimes', 'in:after,never'],
            'show_results' => ['sometimes', 'boolean'],
            'show_answers' => ['sometimes', 'boolean'],
            'show_explanations' => ['sometimes', 'boolean'],
            'allow_review' => ['sometimes', 'boolean'],
            'prevent_tab_switch' => ['sometimes', 'boolean'],
        ], [
            'title.required' => 'اكتب اسم الامتحان.',
            'grade_id.required' => 'اختار السنة الدراسية.',
        ]);

        if ($creating) {
            $data['permanent'] = (bool) ($data['permanent'] ?? true);
            $data['instructions'] = $data['instructions'] ?? null;
            $data['duration_minutes'] = $data['duration_minutes'] ?? null;
            $data['pass_percent'] = $data['pass_percent'] ?? null;
            $data['closes_at'] = $data['closes_at'] ?? null;
        }

        return $data;
    }
}
