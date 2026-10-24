<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExamSession
{
    public function __construct(
        private readonly ExamGate $gate,
        private readonly QuestionBankWriter $banks,
        private readonly QuestionGrader $grader,
    ) {}

    /**
     * @param  array{allowed: bool, message: ?string}  $decision
     * @return array<string, mixed>
     */
    public function catalog(User $student, Exam $exam, array $decision): array
    {
        $attempts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->get();
        $open = $attempts->first(fn (ExamAttempt $attempt) => $attempt->submitted_at === null);
        $done = $attempts->filter(fn (ExamAttempt $attempt) => $attempt->submitted_at !== null);
        $allowed = (int) $exam->attempts_allowed;
        $status = 'available';
        if ($open) {
            $status = 'in_progress';
        } elseif ($allowed > 0 && $done->count() >= $allowed) {
            $status = 'exhausted';
        } elseif ($done->isNotEmpty()) {
            $status = 'completed';
        }

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'permanent' => $exam->permanent,
            'duration_minutes' => $exam->duration_minutes,
            'question_count' => $exam->questions_count ?? $exam->questions()->count(),
            'locked' => ! $decision['allowed'],
            'message' => $decision['message'],
            'status' => $decision['allowed'] ? $status : 'locked',
            'attempts_used' => $done->count(),
            'attempts_allowed' => $allowed,
            'best_score' => $exam->show_results && $done->isNotEmpty() ? $this->number($done->max('score')) : null,
            'month' => $exam->month?->title,
            'month_id' => $exam->study_month_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paper(User $student, Exam $exam): array
    {
        $decision = $this->gate->decide($student, $exam);
        $latest = $this->latest($student, $exam);
        if ($latest && $latest->submitted_at === null && $decision['allowed'] && $this->expired($exam, $latest)) {
            $this->finish($exam, $latest, $this->stored($latest), true);
            $latest = $latest->refresh();
        }

        $card = [
            'id' => $exam->id,
            'title' => $exam->title,
            'instructions' => $exam->instructions,
            'permanent' => $exam->permanent,
            'duration_minutes' => $exam->duration_minutes,
            'pass_percent' => $exam->pass_percent,
            'attempts_allowed' => (int) $exam->attempts_allowed,
            'prevent_tab_switch' => (bool) $exam->prevent_tab_switch,
            'question_count' => $exam->questions()->count(),
            'locked' => ! $decision['allowed'],
            'message' => $decision['message'],
        ];

        if (! $decision['allowed']) {
            return $card;
        }

        if ($latest && $latest->submitted_at) {
            $used = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $student->id)
                ->whereNotNull('submitted_at')
                ->count();
            $card['attempts_used'] = $used;
            $card['can_retry'] = (int) $exam->attempts_allowed === 0 || $used < (int) $exam->attempts_allowed;
            if (! $exam->show_results) {
                $card['hidden_result'] = true;
                $card['message'] = 'النتيجة هتظهر لاحقاً.';

                return $card;
            }
            $card['result'] = $this->result($latest);

            return $card;
        }

        if (! $latest) {
            $card['questions'] = [];

            return $card;
        }

        $exam->load('questions.options');
        $order = collect($this->presentation($exam, $latest))->values();
        $saved = $latest->answers()->get()->keyBy('question_id');
        $byId = $exam->questions->keyBy('id');
        $card['attempt'] = [
            'id' => $latest->id,
            'started_at' => $latest->started_at,
            'tab_switches' => (int) $latest->tab_switches,
        ];
        $card['questions'] = $order->map(function (array $row, int $index) use ($byId, $saved) {
            $question = $byId->get((int) ($row['id'] ?? 0));
            if (! $question) {
                return null;
            }
            $spec = $this->spec($question, false);
            $optionOrder = collect($row['options'] ?? [])->map(fn ($id) => (int) $id);
            if ($optionOrder->isNotEmpty()) {
                $spec['options'] = collect($spec['options'] ?? [])->sortBy(fn ($option) => $optionOrder->search((int) $option['id']) === false ? 999 : $optionOrder->search((int) $option['id']))->values()->all();
            }
            $answer = $saved->get($question->id);

            return [
                ...$spec,
                'points' => $this->number($question->pivot->points),
                'selected' => $answer?->selected ?? [],
                'response' => $answer?->response,
                'order' => $index,
            ];
        })->filter()->values();

        return $card;
    }

    /**
     * @param  list<array{question_id: int, option_ids?: list<int>, response?: ?string, answer?: mixed}>  $answers
     */
    public function save(User $student, Exam $exam, array $answers): void
    {
        $this->gate->assertOpen($student, $exam);
        $attempt = $this->open($student, $exam);
        if ($this->expired($exam, $attempt)) {
            $this->finish($exam, $attempt, $this->stored($attempt), true);
            throw new HttpException(410, 'الوقت خلص، والامتحان اتسلم.');
        }

        $exam->load('questions.options');
        $known = $exam->questions->keyBy('id');
        $sheet = $attempt->answer_sheet ?? [];

        DB::transaction(function () use ($attempt, $answers, $known, &$sheet) {
            foreach ($answers as $row) {
                $question = $known->get((int) $row['question_id']);
                if (! $question) {
                    throw ValidationException::withMessages([
                        'question_id' => ['السؤال مش في الامتحان.'],
                    ]);
                }
                $owned = collect($this->spec($question, true)['options'] ?? [])->pluck('id')->map(fn ($id) => (int) $id);
                $picked = collect($row['option_ids'] ?? [])->map(fn ($id) => (int) $id)->intersect($owned)->unique()->values();
                $response = isset($row['response']) ? mb_substr((string) $row['response'], 0, 20000) : null;
                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'selected' => $picked->all(),
                        'response' => $response,
                        'correct' => false,
                        'awarded' => 0,
                        'pending' => false,
                        'max_points' => $question->pivot->points,
                    ],
                );
                $sheet[(string) $question->id] = $row['answer'] ?? ($response !== null && $response !== '' ? $response : $picked->all());
            }
            $attempt->update(['answer_sheet' => $sheet]);
        });
    }

    public function start(User $student, Exam $exam, ?string $ip = null, ?string $agent = null): ExamAttempt
    {
        $this->gate->assertOpen($student, $exam);
        if ($exam->questions()->count() < 1) {
            throw ValidationException::withMessages([
                'exam' => ['الامتحان لسه من غير أسئلة.'],
            ]);
        }

        $open = $this->findOpen($student, $exam);
        if ($open) {
            if ($this->expired($exam, $open)) {
                $this->finish($exam, $open, $this->stored($open), true);
                throw new HttpException(410, 'الوقت خلص، والامتحان اتسلم.');
            }
            $this->presentation($exam, $open);

            return $open;
        }

        $used = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->whereNotNull('submitted_at')
            ->count();
        $allowed = (int) $exam->attempts_allowed;
        if ($allowed > 0 && $used >= $allowed) {
            throw ValidationException::withMessages([
                'exam' => ['خلّصت محاولات الامتحان.'],
            ]);
        }

        $attempt = ExamAttempt::query()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
            'ip_address' => $ip ? mb_substr($ip, 0, 45) : null,
            'user_agent' => $agent ? mb_substr($agent, 0, 255) : null,
        ]);
        $this->presentation($exam, $attempt);

        return $attempt;
    }

    /**
     * @return array{submitted: bool, tab_switches: int}
     */
    public function tab(User $student, Exam $exam): array
    {
        $this->gate->assertOpen($student, $exam);
        $attempt = $this->findOpen($student, $exam);
        if (! $attempt) {
            throw ValidationException::withMessages([
                'exam' => ['ابدأ الامتحان الأول.'],
            ]);
        }
        if ($this->expired($exam, $attempt)) {
            $this->finish($exam, $attempt, $this->stored($attempt), true);

            return ['submitted' => true, 'tab_switches' => (int) $attempt->tab_switches];
        }

        $activity = $attempt->activity ?? [];
        $activity[] = [
            'activity' => 'tab_switch',
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
        $attempt->update([
            'tab_switches' => (int) $attempt->tab_switches + 1,
            'activity' => $activity,
        ]);
        $attempt->refresh();

        if ($exam->prevent_tab_switch && (int) $attempt->tab_switches >= 3) {
            $this->finish($exam, $attempt, $this->stored($attempt), true);

            return ['submitted' => true, 'tab_switches' => (int) $attempt->tab_switches];
        }

        return ['submitted' => false, 'tab_switches' => (int) $attempt->tab_switches];
    }

    /**
     * @param  list<array{question_id: int, option_ids?: list<int>, response?: ?string, answer?: mixed}>  $answers
     * @return array<string, mixed>
     */
    public function submit(User $student, Exam $exam, array $answers): array
    {
        $this->gate->assertOpen($student, $exam);
        $attempt = $this->findOpen($student, $exam);
        if (! $attempt) {
            throw ValidationException::withMessages([
                'exam' => ['ابدأ الامتحان الأول.'],
            ]);
        }

        $auto = $this->expired($exam, $attempt);

        return $this->finish($exam, $attempt, $answers, $auto);
    }

    /**
     * @param  list<array{question_id: int, option_ids?: list<int>, response?: ?string, answer?: mixed}>  $answers
     * @return array<string, mixed>
     */
    private function finish(Exam $exam, ExamAttempt $attempt, array $answers, bool $auto): array
    {
        if ($attempt->submitted_at) {
            return $exam->show_results
                ? $this->result($attempt)
                : ['hidden_result' => true, 'message' => 'النتيجة هتظهر لاحقاً.'];
        }

        $exam->load('questions.options');
        $byQuestion = collect($answers)->keyBy(fn (array $row) => (int) $row['question_id']);
        $score = 0.0;
        $total = 0.0;
        $sheet = [];

        DB::transaction(function () use ($exam, $attempt, $byQuestion, $auto, &$score, &$total, &$sheet) {
            $attempt->answers()->delete();
            foreach ($exam->questions as $question) {
                $points = (float) $question->pivot->points;
                $total += $points;
                $row = $byQuestion->get($question->id) ?? [];
                $graded = $this->grader->score($this->spec($question, true), [
                    'option_ids' => $row['option_ids'] ?? [],
                    'response' => $row['response'] ?? null,
                    'answer' => $row['answer'] ?? null,
                ], $points);
                $score += $graded['awarded'];
                $response = isset($row['response']) ? mb_substr((string) $row['response'], 0, 20000) : null;
                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'selected' => collect($row['option_ids'] ?? [])->map(fn ($id) => (int) $id)->values()->all(),
                    'response' => $response,
                    'correct' => $graded['correct'],
                    'awarded' => $graded['awarded'],
                    'pending' => $graded['pending'],
                    'max_points' => $points,
                ]);
                $sheet[(string) $question->id] = $row['answer'] ?? ($response !== null && $response !== '' ? $response : ($row['option_ids'] ?? []));
            }

            $taken = $attempt->started_at instanceof Carbon
                ? (int) abs($attempt->started_at->diffInSeconds(Carbon::now()))
                : null;
            $attempt->update([
                'status' => 'completed',
                'score' => round($score, 2),
                'total' => round($total, 2),
                'percentage' => $total > 0 ? round(($score / $total) * 100, 2) : 0,
                'submitted_at' => Carbon::now(),
                'time_taken' => $taken,
                'auto_submitted' => $auto,
                'answer_sheet' => $sheet,
            ]);
        });

        $attempt->refresh();
        if (! $exam->show_results) {
            return ['hidden_result' => true, 'message' => 'النتيجة هتظهر لاحقاً.'];
        }

        return $this->result($attempt);
    }

    /**
     * @return list<array{question_id: int, option_ids: list<int>, response: ?string, answer: mixed}>
     */
    private function stored(ExamAttempt $attempt): array
    {
        return $attempt->answers()->get()->map(fn ($answer) => [
            'question_id' => (int) $answer->question_id,
            'option_ids' => is_array($answer->selected) ? $answer->selected : [],
            'response' => $answer->response,
            'answer' => $attempt->answer_sheet[(string) $answer->question_id] ?? null,
        ])->all();
    }

    private function open(User $student, Exam $exam): ExamAttempt
    {
        return $this->findOpen($student, $exam) ?? $this->start($student, $exam);
    }

    private function findOpen(User $student, Exam $exam): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->whereNull('submitted_at')
            ->latest('id')
            ->first();
    }

    private function expired(Exam $exam, ExamAttempt $attempt): bool
    {
        if (! $exam->duration_minutes || ! $attempt->started_at) {
            return false;
        }

        return $attempt->started_at->copy()->addMinutes((int) $exam->duration_minutes)->lessThanOrEqualTo(Carbon::now());
    }

    /**
     * @return list<array{id: int, options: list<int>}>
     */
    private function presentation(Exam $exam, ExamAttempt $attempt): array
    {
        $stored = $attempt->presentation;
        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        $exam->loadMissing('questions.options');
        $questions = $exam->questions->values();
        if ($exam->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }
        $rows = [];
        foreach ($questions as $question) {
            $optionIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($exam->shuffle_choices && $optionIds !== []) {
                shuffle($optionIds);
            }
            $rows[] = ['id' => (int) $question->id, 'options' => $optionIds];
        }
        $attempt->update(['presentation' => $rows]);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function result(ExamAttempt $attempt): array
    {
        $attempt->load('answers', 'exam.questions.options');
        $exam = $attempt->exam;
        $percent = $attempt->total > 0 ? (int) round(($attempt->score / $attempt->total) * 100) : 0;
        $passing = $exam->passing_marks;
        $passed = $passing !== null
            ? $attempt->score >= (float) $passing
            : ($exam->pass_percent === null ? null : $percent >= (int) $exam->pass_percent);
        $answers = $attempt->answers->keyBy('question_id');
        $showWhy = (bool) $exam->show_explanations;
        $showAnswers = (bool) $exam->show_answers;
        $review = (bool) $exam->allow_review;

        $payload = [
            'score' => $this->number($attempt->score),
            'total' => $this->number($attempt->total),
            'percent' => $percent,
            'passed' => $passed,
            'pending' => $attempt->answers->contains(fn ($answer) => $answer->pending),
            'submitted_at' => $attempt->submitted_at,
            'auto_submitted' => (bool) $attempt->auto_submitted,
            'questions' => [],
        ];

        if (! $review) {
            return $payload;
        }

        $payload['questions'] = $exam->questions->map(function (Question $question) use ($answers, $showWhy, $showAnswers) {
            $answer = $answers->get($question->id);
            $spec = $this->spec($question, $showAnswers);
            if (! $showWhy) {
                unset($spec['explanation']);
            }
            if (! $showAnswers) {
                unset($spec['explanation']);
            }

            return [
                ...$spec,
                'points' => $this->number($question->pivot->points),
                'selected' => $answer?->selected ?? [],
                'response' => $answer?->response,
                'awarded' => $answer ? $this->number($answer->awarded) : 0,
                'pending' => (bool) $answer?->pending,
                'feedback' => $answer?->feedback,
                'correct' => $showAnswers ? (bool) $answer?->correct : null,
            ];
        })->values();

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function spec(Question $question, bool $withAnswers): array
    {
        $raw = $question->pivot->snapshot ?? null;
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw) || ! isset($raw['type'])) {
            return $this->banks->questionPayload($question, $withAnswers);
        }
        if ($withAnswers) {
            return $raw;
        }
        $hidden = $raw;
        unset($hidden['explanation'], $hidden['negative_mark']);
        $hidden['options'] = collect($hidden['options'] ?? [])->map(function ($option) {
            unset($option['is_correct']);

            return $option;
        })->values();
        if (in_array($hidden['type'] ?? '', ['numerical', 'short', 'fill_blank'], true)) {
            unset($hidden['settings']);
        }
        if (($hidden['type'] ?? '') === 'essay') {
            $hidden['settings'] = [
                'min_words' => (int) ($raw['settings']['min_words'] ?? 0),
                'max_words' => (int) ($raw['settings']['max_words'] ?? 0),
            ];
        }
        if (($hidden['type'] ?? '') === 'true_false') {
            unset($hidden['settings']);
        }

        return $hidden;
    }

    private function number(mixed $value): int|float
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (int) $number : $number;
    }

    private function latest(User $student, Exam $exam): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->latest('id')
            ->first();
    }
}
