<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\Exams\ExamGate;
use App\Services\Exams\ExamSession;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamGate $gate,
        private readonly ExamSession $sessions,
    ) {}

    public function index(Request $request)
    {
        $student = $request->user();
        if (! $student->grade_id) {
            return response()->json([]);
        }

        $rows = Exam::query()
            ->where('grade_id', $student->grade_id)
            ->where('published', true)
            ->where('active', true)
            ->with('month:id,title')
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();

        return response()->json($rows->map(function (Exam $exam) use ($student) {
            $decision = $this->gate->decide($student, $exam);

            return $this->sessions->catalog($student, $exam, $decision);
        }));
    }

    public function show(Request $request, Exam $exam)
    {
        $decision = $this->gate->decide($request->user(), $exam);
        abort_if($decision['status'] === 404, 404);

        return response()->json($this->sessions->paper($request->user(), $exam));
    }

    public function start(Request $request, Exam $exam)
    {
        $attempt = $this->sessions->start($request->user(), $exam, $request->ip(), $request->userAgent());

        return response()->json([
            'id' => $attempt->id,
            'started_at' => $attempt->started_at,
        ]);
    }

    public function save(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'answers' => ['required_without:question_id', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.option_ids' => ['nullable', 'array'],
            'answers.*.option_ids.*' => ['integer'],
            'answers.*.response' => ['nullable', 'string', 'max:20000'],
            'answers.*.answer' => ['nullable'],
            'question_id' => ['required_without:answers', 'integer'],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer'],
            'response' => ['nullable', 'string', 'max:20000'],
            'answer' => ['nullable'],
        ]);
        $rows = $data['answers'] ?? [[
            'question_id' => $data['question_id'],
            'option_ids' => $data['option_ids'] ?? [],
            'response' => $data['response'] ?? null,
            'answer' => $data['answer'] ?? null,
        ]];
        $this->sessions->save($request->user(), $exam, array_map(fn (array $row) => [
            'question_id' => (int) $row['question_id'],
            'option_ids' => array_map('intval', $row['option_ids'] ?? []),
            'response' => $row['response'] ?? null,
            'answer' => $row['answer'] ?? null,
        ], $rows));

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'answers' => ['present', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.option_ids' => ['nullable', 'array'],
            'answers.*.option_ids.*' => ['integer'],
            'answers.*.response' => ['nullable', 'string', 'max:20000'],
            'answers.*.answer' => ['nullable'],
        ], [
            'answers.*.question_id.required' => 'الإجابة ناقصة.',
        ]);

        $result = $this->sessions->submit($request->user(), $exam, array_map(fn (array $row) => [
            'question_id' => (int) $row['question_id'],
            'option_ids' => array_map('intval', $row['option_ids'] ?? []),
            'response' => $row['response'] ?? null,
            'answer' => $row['answer'] ?? null,
        ], $data['answers']));

        return response()->json($result);
    }

    public function tab(Request $request, Exam $exam)
    {
        return response()->json($this->sessions->tab($request->user(), $exam));
    }
}
