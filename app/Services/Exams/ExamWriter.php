<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Grade;
use App\Models\Question;
use App\Models\StudyMonth;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ExamWriter
{
    public function __construct(private readonly QuestionBankWriter $banks) {}

    /**
     * @param  array{title: string, instructions: ?string, permanent: bool, duration_minutes: ?int, pass_percent: ?int, closes_at: ?string}  $fields
     */
    public function create(Grade $grade, array $fields): Exam
    {
        return Exam::query()->create([
            'grade_id' => $grade->id,
            'study_month_id' => $this->monthId($grade->id, $fields['study_month_id'] ?? null),
            'title' => $fields['title'],
            'instructions' => $fields['instructions'],
            'permanent' => $fields['permanent'],
            'duration_minutes' => $fields['duration_minutes'],
            'pass_percent' => $fields['pass_percent'],
            'closes_at' => $fields['permanent'] ? null : $this->closesAt($fields['closes_at'] ?? null),
            'opens_at' => $this->closesAt($fields['opens_at'] ?? null),
            'attempts_allowed' => (int) ($fields['attempts_allowed'] ?? 0),
            'show_results' => (bool) ($fields['show_results'] ?? true),
            'show_answers' => (bool) ($fields['show_answers'] ?? true),
            'show_explanations' => (bool) ($fields['show_explanations'] ?? true),
            'allow_review' => (bool) ($fields['allow_review'] ?? true),
            'prevent_tab_switch' => (bool) ($fields['prevent_tab_switch'] ?? false),
            'active' => true,
            'published' => false,
        ]);
    }

    /**
     * @param  array{title?: string, instructions?: ?string, permanent?: bool, duration_minutes?: ?int, pass_percent?: ?int, closes_at?: ?string, published?: bool}  $fields
     */
    public function update(Exam $exam, array $fields): Exam
    {
        $permanent = array_key_exists('permanent', $fields) ? (bool) $fields['permanent'] : $exam->permanent;

        $showExplanations = array_key_exists('show_explanations', $fields)
            ? (bool) $fields['show_explanations']
            : (array_key_exists('explanation_when', $fields) ? $fields['explanation_when'] !== 'never' : $exam->show_explanations);

        $exam->update([
            'title' => $fields['title'] ?? $exam->title,
            'instructions' => array_key_exists('instructions', $fields) ? $fields['instructions'] : $exam->instructions,
            'permanent' => $permanent,
            'duration_minutes' => array_key_exists('duration_minutes', $fields) ? $fields['duration_minutes'] : $exam->duration_minutes,
            'pass_percent' => array_key_exists('pass_percent', $fields) ? $fields['pass_percent'] : $exam->pass_percent,
            'closes_at' => $permanent ? null : (array_key_exists('closes_at', $fields) ? $this->closesAt($fields['closes_at']) : $exam->closes_at),
            'opens_at' => array_key_exists('opens_at', $fields) ? $this->closesAt($fields['opens_at']) : $exam->opens_at,
            'study_month_id' => array_key_exists('study_month_id', $fields) ? $this->monthId($exam->grade_id, $fields['study_month_id']) : $exam->study_month_id,
            'attempts_allowed' => array_key_exists('attempts_allowed', $fields) ? (int) $fields['attempts_allowed'] : $exam->attempts_allowed,
            'published' => array_key_exists('published', $fields) ? (bool) $fields['published'] : $exam->published,
            'active' => array_key_exists('active', $fields) ? (bool) $fields['active'] : $exam->active,
            'shuffle_questions' => array_key_exists('shuffle_questions', $fields) ? (bool) $fields['shuffle_questions'] : $exam->shuffle_questions,
            'shuffle_choices' => array_key_exists('shuffle_choices', $fields) ? (bool) $fields['shuffle_choices'] : $exam->shuffle_choices,
            'explanation_when' => $showExplanations ? 'after' : 'never',
            'show_results' => array_key_exists('show_results', $fields) ? (bool) $fields['show_results'] : $exam->show_results,
            'show_answers' => array_key_exists('show_answers', $fields) ? (bool) $fields['show_answers'] : $exam->show_answers,
            'show_explanations' => $showExplanations,
            'allow_review' => array_key_exists('allow_review', $fields) ? (bool) $fields['allow_review'] : $exam->allow_review,
            'prevent_tab_switch' => array_key_exists('prevent_tab_switch', $fields) ? (bool) $fields['prevent_tab_switch'] : $exam->prevent_tab_switch,
        ]);

        $this->recount($exam->refresh());

        return $exam->refresh();
    }

    /**
     * @param  list<array{id: int, points: int}>  $items
     */
    public function syncQuestions(Exam $exam, array $items): void
    {
        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $found = Question::query()
            ->with('options')
            ->whereIn('id', $ids)
            ->whereHas('lesson.unit.bank', fn ($query) => $query->where('grade_id', $exam->grade_id))
            ->get()
            ->keyBy('id');

        if ($found->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'questions' => ['اختار أسئلة من بنك نفس السنة الدراسية.'],
            ]);
        }

        if ($found->contains(fn (Question $question) => $question->status !== 'published')) {
            throw ValidationException::withMessages([
                'questions' => ['الامتحان بياخد الأسئلة المنشورة بس. انشر المسودة الأول.'],
            ]);
        }

        $sync = [];
        foreach ($items as $index => $item) {
            $id = (int) $item['id'];
            $question = $found->get($id);
            if (! $question || isset($sync[$id])) {
                continue;
            }
            $points = round((float) ($item['points'] ?? $question->marks ?? 1), 2);
            if ($points < 0.5) {
                throw ValidationException::withMessages([
                    'questions' => ['درجة السؤال في الامتحان تبدأ من نص درجة.'],
                ]);
            }
            $sync[$id] = [
                'points' => $points,
                'sort' => $index,
                'snapshot' => json_encode($this->banks->questionPayload($question, true), JSON_UNESCAPED_UNICODE),
            ];
        }

        $exam->questions()->sync($sync);
        $this->recount($exam);
    }

    /**
     * @param  array{unit?: int, lesson?: int, difficulty?: string, count: int}  $rule
     */
    public function generate(Exam $exam, array $rule): int
    {
        $bucket = (string) ($rule['difficulty'] ?? '');
        $picked = Question::query()
            ->with('options')
            ->where('status', 'published')
            ->whereHas('lesson.unit.bank', fn ($query) => $query->where('grade_id', $exam->grade_id))
            ->when(! empty($rule['lesson']), fn ($query) => $query->where('bank_lesson_id', (int) $rule['lesson']))
            ->when(! empty($rule['unit']) && empty($rule['lesson']), fn ($query) => $query->whereHas('lesson', fn ($lesson) => $lesson->where('bank_unit_id', (int) $rule['unit'])))
            ->when($bucket === 'easy', fn ($query) => $query->where('difficulty', '<=', 2))
            ->when($bucket === 'medium', fn ($query) => $query->where('difficulty', 3))
            ->when($bucket === 'hard', fn ($query) => $query->where('difficulty', '>=', 4))
            ->whereNotIn('id', $exam->questions()->pluck('questions.id'))
            ->inRandomOrder()
            ->limit(max(1, min(50, (int) $rule['count'])))
            ->get();

        if ($picked->isEmpty()) {
            throw ValidationException::withMessages([
                'count' => ['مفيش أسئلة منشورة تطابق الاختيار.'],
            ]);
        }

        $current = $exam->questions()->get();
        $items = $current->map(fn (Question $question) => [
            'id' => $question->id,
            'points' => (float) $question->pivot->points,
        ])->all();
        foreach ($picked as $question) {
            $items[] = ['id' => $question->id, 'points' => max(0.5, round((float) $question->marks, 2))];
        }
        $this->syncQuestions($exam, $items);

        return $picked->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function attempts(Exam $exam): array
    {
        $done = $exam->attempts()->whereNotNull('submitted_at')->get();
        $scores = $done->pluck('score')->map(fn ($score) => (float) $score);
        $passing = $exam->passing_marks;
        $passed = $passing === null
            ? null
            : $done->filter(fn ($attempt) => (float) $attempt->score >= (float) $passing)->count();

        $rows = $exam->attempts()
            ->with(['user', 'answers.question'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->limit(40)
            ->get()
            ->map(function ($attempt) {
                $name = trim(($attempt->user?->first_name ?? '').' '.($attempt->user?->second_name ?? ''));

                return [
                    'id' => $attempt->id,
                    'student' => $name !== '' ? $name : ($attempt->user?->name ?? 'طالب'),
                    'score' => $this->number($attempt->score),
                    'total' => $this->number($attempt->total),
                    'submitted_at' => $attempt->submitted_at,
                    'pending' => $attempt->answers->where('pending', true)->map(fn (ExamAnswer $answer) => [
                        'id' => $answer->id,
                        'prompt' => $answer->question?->prompt,
                        'response' => $answer->response,
                        'model' => $answer->question?->settings['model_answer'] ?? null,
                        'max_points' => $answer->max_points,
                    ])->values(),
                ];
            })->all();

        return [
            'stats' => [
                'count' => $done->count(),
                'average' => $scores->isEmpty() ? null : $this->number(round($scores->avg(), 2)),
                'highest' => $scores->isEmpty() ? null : $this->number($scores->max()),
                'lowest' => $scores->isEmpty() ? null : $this->number($scores->min()),
                'pass_rate' => $passed === null || $done->isEmpty() ? null : (int) round(($passed / $done->count()) * 100),
            ],
            'rows' => $rows,
        ];
    }

    public function grade(ExamAnswer $answer, float $awarded, ?string $feedback): void
    {
        $attempt = $answer->attempt()->first();
        if (! $attempt || ! $attempt->submitted_at) {
            throw ValidationException::withMessages(['answer' => ['المحاولة لسه متسلمتش.']]);
        }
        $cap = (float) $answer->max_points;
        $answer->update([
            'awarded' => round(max(0, min($cap, $awarded)), 2),
            'feedback' => $feedback,
            'pending' => false,
            'correct' => $awarded > 0,
        ]);
        $attempt->update([
            'score' => round((float) $attempt->answers()->sum('awarded'), 2),
        ]);
    }

    private function number(mixed $value): int|float
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (int) $number : $number;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Exam $exam): array
    {
        $exam->load(['grade', 'questions.options', 'questions.lesson.unit']);

        return [
            'exam' => $this->card($exam, true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function card(Exam $exam, bool $withQuestions = false): array
    {
        $exam->loadMissing('grade', 'month');
        $row = [
            'id' => $exam->id,
            'title' => $exam->title,
            'instructions' => $exam->instructions,
            'grade_id' => $exam->grade_id,
            'grade' => $exam->grade?->title,
            'study_month_id' => $exam->study_month_id,
            'month' => $exam->month?->title,
            'published' => $exam->published,
            'active' => (bool) $exam->active,
            'permanent' => $exam->permanent,
            'duration_minutes' => $exam->duration_minutes,
            'pass_percent' => $exam->pass_percent,
            'passing_marks' => $exam->passing_marks,
            'total_marks' => $this->number($exam->total_marks),
            'attempts_allowed' => (int) $exam->attempts_allowed,
            'closes_at' => $exam->closes_at,
            'opens_at' => $exam->opens_at,
            'shuffle_questions' => (bool) $exam->shuffle_questions,
            'shuffle_choices' => (bool) $exam->shuffle_choices,
            'explanation_when' => $exam->show_explanations ? 'after' : 'never',
            'show_results' => (bool) $exam->show_results,
            'show_answers' => (bool) $exam->show_answers,
            'show_explanations' => (bool) $exam->show_explanations,
            'allow_review' => (bool) $exam->allow_review,
            'prevent_tab_switch' => (bool) $exam->prevent_tab_switch,
            'question_count' => $exam->questions()->count(),
        ];

        if ($withQuestions) {
            $exam->loadMissing('questions.options', 'questions.lesson.unit');
            $row['questions'] = $exam->questions->map(function (Question $question) {
                return [
                    ...$this->banks->questionPayload($question, true),
                    'points' => $this->number($question->pivot->points),
                    'unit' => $question->lesson?->unit?->title,
                    'lesson' => $question->lesson?->title,
                ];
            })->values();
        }

        return $row;
    }

    private function recount(Exam $exam): void
    {
        $total = round((float) $exam->questions()->sum('exam_questions.points'), 2);
        $percent = $exam->pass_percent;

        $exam->update([
            'total_marks' => $total,
            'passing_marks' => $percent === null ? $exam->passing_marks : round($total * ((int) $percent) / 100, 2),
        ]);
    }

    private function monthId(int $gradeId, mixed $monthId): ?int
    {
        if ($monthId === null || $monthId === '') {
            return null;
        }
        $month = StudyMonth::query()->find((int) $monthId);
        if (! $month || (int) $month->grade_id !== $gradeId) {
            throw ValidationException::withMessages([
                'study_month_id' => ['الشهر مش من نفس السنة الدراسية.'],
            ]);
        }

        return $month->id;
    }

    private function closesAt(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
