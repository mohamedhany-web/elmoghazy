<?php

namespace App\Services\Exams;

use App\Models\BankLesson;
use App\Models\BankTopic;
use App\Models\BankUnit;
use App\Models\Grade;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionBankWriter
{
    public function open(Grade $grade): QuestionBank
    {
        return QuestionBank::query()->firstOrCreate(['grade_id' => $grade->id]);
    }

    /**
     * @return array<string, mixed>
     */
    public function outline(Grade $grade): array
    {
        $bank = $this->open($grade);
        $bank->load('units');
        $lessonCounts = BankLesson::query()
            ->whereIn('bank_unit_id', $bank->units->pluck('id'))
            ->with('topics')
            ->withCount('questions')
            ->orderBy('sort')
            ->get()
            ->groupBy('bank_unit_id');

        $units = $bank->units->map(function (BankUnit $unit) use ($lessonCounts) {
            $lessons = $lessonCounts->get($unit->id, collect());

            return [
                'id' => $unit->id,
                'title' => $unit->title,
                'questions' => (int) $lessons->sum('questions_count'),
                'lessons' => $lessons->map(fn (BankLesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'questions' => (int) $lesson->questions_count,
                    'topics' => $lesson->topics->map(fn (BankTopic $topic) => [
                        'id' => $topic->id,
                        'title' => $topic->title,
                    ])->values(),
                ])->values(),
            ];
        })->values();

        $questions = Question::query()->whereHas('lesson.unit', fn ($unit) => $unit->where('question_bank_id', $bank->id));

        return [
            'grade' => ['id' => $grade->id, 'title' => $grade->title],
            'counts' => [
                'units' => $units->count(),
                'lessons' => $units->sum(fn (array $unit) => count($unit['lessons'])),
                'topics' => $units->sum(fn (array $unit) => collect($unit['lessons'])->sum(fn (array $lesson) => count($lesson['topics']))),
                'questions' => (clone $questions)->count(),
                'draft' => (clone $questions)->where('status', 'draft')->count(),
                'published' => (clone $questions)->where('status', 'published')->count(),
                'archived' => (clone $questions)->where('status', 'archived')->count(),
                'easy' => (clone $questions)->where('difficulty', '<=', 2)->count(),
                'medium' => (clone $questions)->where('difficulty', 3)->count(),
                'hard' => (clone $questions)->where('difficulty', '>=', 4)->count(),
                'used' => (clone $questions)->whereHas('exams')->count(),
            ],
            'units' => $units,
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, page: int, last_page: int, total: int}
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function browse(Grade $grade, array $filters): array
    {
        $bank = $this->open($grade);
        $term = trim((string) ($filters['q'] ?? ''));
        $safe = str_replace(['%', '_'], ['\\%', '\\_'], $term);
        $lessonId = isset($filters['lesson']) ? (int) $filters['lesson'] : null;
        $unitId = isset($filters['unit']) ? (int) $filters['unit'] : null;
        $page = (int) ($filters['page'] ?? 1);
        $bucket = (string) ($filters['difficulty'] ?? '');

        $query = Question::query()
            ->whereHas('lesson.unit', fn ($unit) => $unit->where('question_bank_id', $bank->id))
            ->with(['options', 'lesson.unit', 'topic'])
            ->withCount([
                'exams as usage_count',
                'answers as answer_count' => fn ($answers) => $answers->where('pending', false)->whereHas('attempt', fn ($attempt) => $attempt->whereNotNull('submitted_at')),
                'answers as correct_count' => fn ($answers) => $answers->where('correct', true)->where('pending', false)->whereHas('attempt', fn ($attempt) => $attempt->whereNotNull('submitted_at')),
            ])
            ->when($lessonId, fn ($rows) => $rows->where('bank_lesson_id', $lessonId))
            ->when($unitId && ! $lessonId, fn ($rows) => $rows->whereHas('lesson', fn ($lesson) => $lesson->where('bank_unit_id', $unitId)))
            ->when(! empty($filters['topic']), fn ($rows) => $rows->where('bank_topic_id', (int) $filters['topic']))
            ->when(! empty($filters['type']), fn ($rows) => $rows->where('type', $filters['type']))
            ->when(! empty($filters['status']), fn ($rows) => $rows->where('status', $filters['status']))
            ->when($bucket === 'easy', fn ($rows) => $rows->where('difficulty', '<=', 2))
            ->when($bucket === 'medium', fn ($rows) => $rows->where('difficulty', 3))
            ->when($bucket === 'hard', fn ($rows) => $rows->where('difficulty', '>=', 4))
            ->when($safe !== '', function ($rows) use ($safe) {
                $rows->where(function ($inner) use ($safe) {
                    $inner->where('prompt', 'like', '%'.$safe.'%')->orWhere('tags', 'like', '%'.$safe.'%');
                });
            })
            ->orderByDesc('id');

        $pageSize = 20;
        $total = (clone $query)->count();
        $last = max(1, (int) ceil($total / $pageSize));
        $page = min(max(1, $page), $last);
        $rows = $query->forPage($page, $pageSize)->get();

        return [
            'data' => $rows->map(function (Question $question) {
                $correct = $question->options->where('is_correct', true)->pluck('body')->take(2)->implode(' · ');
                if ($correct === '' && $question->type === 'numerical') {
                    $correct = (string) ($question->settings['answer'] ?? '');
                }
                $answered = (int) $question->answer_count;

                return [
                    ...$this->questionPayload($question, true),
                    'lesson_id' => $question->bank_lesson_id,
                    'topic_id' => $question->bank_topic_id,
                    'topic' => $question->topic?->title,
                    'lesson' => $question->lesson?->title,
                    'unit' => $question->lesson?->unit?->title,
                    'unit_id' => $question->lesson?->bank_unit_id,
                    'correct_label' => $correct,
                    'usage' => (int) $question->usage_count,
                    'correct_rate' => $answered > 0 ? (int) round(((int) $question->correct_count / $answered) * 100) : null,
                ];
            })->values()->all(),
            'page' => $page,
            'last_page' => $last,
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tree(Grade $grade): array
    {
        $bank = $this->open($grade);
        $bank->load('units.lessons.questions.options');

        return [
            'grade' => ['id' => $grade->id, 'title' => $grade->title],
            'units' => $bank->units->map(fn (BankUnit $unit) => [
                'id' => $unit->id,
                'title' => $unit->title,
                'lessons' => $unit->lessons->map(fn (BankLesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'questions' => $lesson->questions->map(fn (Question $question) => $this->questionPayload($question, true)),
                ])->values(),
            ])->values(),
        ];
    }

    public function addUnit(Grade $grade, string $title): BankUnit
    {
        $bank = $this->open($grade);

        return $bank->units()->create([
            'title' => $title,
            'sort' => (int) $bank->units()->max('sort') + 1,
        ]);
    }

    public function renameUnit(BankUnit $unit, string $title): void
    {
        $unit->update(['title' => $title]);
    }

    public function deleteUnit(BankUnit $unit): void
    {
        $unit->delete();
    }

    public function addLesson(BankUnit $unit, string $title): BankLesson
    {
        return $unit->lessons()->create([
            'title' => $title,
            'sort' => (int) $unit->lessons()->max('sort') + 1,
        ]);
    }

    public function renameLesson(BankLesson $lesson, string $title): void
    {
        $lesson->update(['title' => $title]);
    }

    public function deleteLesson(BankLesson $lesson): void
    {
        $lesson->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(BankLesson $lesson, array $data, ?Question $question = null): Question
    {
        $type = $this->typeOf($data);
        $options = $this->optionsFor($type, $data);
        $settings = $this->settingsFor($type, $data);
        $topicId = $this->topicId($lesson, $data['topic_id'] ?? null);

        return DB::transaction(function () use ($lesson, $data, $question, $type, $options, $settings, $topicId) {
            $fields = [
                'bank_lesson_id' => $lesson->id,
                'bank_topic_id' => $topicId,
                'prompt' => trim((string) $data['prompt']),
                'type' => $type,
                'difficulty' => max(1, min(5, (int) ($data['difficulty'] ?? 3))),
                'marks' => max(0.25, (float) ($data['marks'] ?? 1)),
                'negative_mark' => isset($data['negative_mark']) && $data['negative_mark'] !== '' ? (float) $data['negative_mark'] : null,
                'explanation' => $this->blank($data['explanation'] ?? null),
                'hint' => $this->blank($data['hint'] ?? null),
                'status' => in_array($data['status'] ?? 'published', ['draft', 'published', 'archived'], true) ? ($data['status'] ?? 'published') : 'published',
                'tags' => $this->tags($data['tags'] ?? []),
                'settings' => $settings,
                'favorite' => (bool) ($data['favorite'] ?? $question?->favorite ?? false),
            ];

            if ($question) {
                $question->update($fields);
            } else {
                $question = $lesson->questions()->create([
                    ...$fields,
                    'sort' => (int) $lesson->questions()->max('sort') + 1,
                ]);
            }

            $question->options()->delete();
            $this->writeOptions($question, $options);

            return $question->load('options');
        });
    }

    public function duplicate(Question $question): Question
    {
        $question->loadMissing('options', 'lesson');
        $copy = $this->save($question->lesson, [
            'prompt' => $question->prompt,
            'type' => $question->type,
            'difficulty' => $question->difficulty,
            'marks' => $question->marks,
            'negative_mark' => $question->negative_mark,
            'explanation' => $question->explanation,
            'hint' => $question->hint,
            'status' => 'draft',
            'tags' => $question->tags ?? [],
            'topic_id' => $question->bank_topic_id,
            'settings' => $question->settings ?? [],
            'options' => $question->options->map(fn (QuestionOption $option) => [
                'body' => $option->body,
                'is_correct' => $option->is_correct,
            ])->all(),
        ]);

        return $copy;
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $extra
     */
    public function bulk(Grade $grade, array $ids, string $action, array $extra = []): int
    {
        $bank = $this->open($grade);
        $questions = Question::query()
            ->whereIn('id', $ids)
            ->whereHas('lesson.unit', fn ($unit) => $unit->where('question_bank_id', $bank->id))
            ->get();

        if ($action === 'delete') {
            Question::query()->whereIn('id', $questions->pluck('id'))->delete();

            return $questions->count();
        }

        foreach ($questions as $question) {
            if ($action === 'archive') {
                $question->update(['status' => 'archived']);
            } elseif ($action === 'publish') {
                $question->update(['status' => 'published']);
            } elseif ($action === 'draft') {
                $question->update(['status' => 'draft']);
            } elseif ($action === 'difficulty') {
                $question->update(['difficulty' => max(1, min(5, (int) ($extra['difficulty'] ?? 3)))]);
            } elseif ($action === 'tag') {
                $tags = $this->tags(array_merge($question->tags ?? [], [(string) ($extra['tag'] ?? '')]));
                $question->update(['tags' => $tags]);
            } elseif ($action === 'move') {
                $lesson = BankLesson::query()->find((int) ($extra['lesson_id'] ?? 0));
                if (! $lesson || (int) $lesson->unit?->question_bank_id !== (int) $bank->id) {
                    throw ValidationException::withMessages([
                        'lesson_id' => ['الدرس مش من بنك السنة دي.'],
                    ]);
                }
                $question->update([
                    'bank_lesson_id' => $lesson->id,
                    'bank_topic_id' => null,
                ]);
            }
        }

        return $questions->count();
    }

    public function importCsv(BankLesson $lesson, string $csv): int
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        $header = null;
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }
            if ($header === null) {
                $header = array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), $row);
                if (! in_array('prompt', $header, true) && ! in_array('السؤال', $header, true)) {
                    $header = ['prompt', 'type', 'a', 'b', 'c', 'd', 'correct', 'difficulty', 'marks', 'explanation', 'tags'];
                    $this->importRow($lesson, $header, $row);
                    $count++;
                }

                continue;
            }
            $this->importRow($lesson, $header, $row);
            $count++;
            if ($count >= 500) {
                break;
            }
        }
        fclose($handle);

        return $count;
    }

    public function addTopic(BankLesson $lesson, string $title): BankTopic
    {
        return $lesson->topics()->create([
            'title' => $title,
            'sort' => (int) $lesson->topics()->max('sort') + 1,
        ]);
    }

    public function renameTopic(BankTopic $topic, string $title): void
    {
        $topic->update(['title' => $title]);
    }

    public function deleteTopic(BankTopic $topic): void
    {
        $topic->delete();
    }

    public function deleteQuestion(Question $question): void
    {
        if ($question->exams()->exists()) {
            throw ValidationException::withMessages([
                'question' => ['السؤال مربوط بامتحان. شيله من الامتحان الأول.'],
            ]);
        }

        $question->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function typeOf(array $data): string
    {
        $type = (string) ($data['type'] ?? '');
        $allowed = ['single', 'multiple', 'multiple_choice', 'true_false', 'fill_blank', 'numerical', 'short', 'essay'];
        if (in_array($type, $allowed, true)) {
            return $type;
        }
        $correct = collect($data['options'] ?? [])->filter(fn ($option) => ! empty($option['is_correct']))->count();

        return $correct > 1 ? 'multiple' : 'single';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{body: string, is_correct: bool}>
     */
    private function optionsFor(string $type, array $data): array
    {
        if ($type === 'true_false') {
            $correct = (bool) ($data['settings']['correct'] ?? false);

            return [
                ['body' => 'صح', 'is_correct' => $correct],
                ['body' => 'غلط', 'is_correct' => ! $correct],
            ];
        }

        if (! in_array($type, ['single', 'multiple'], true)) {
            return [];
        }

        $options = collect($data['options'] ?? [])
            ->map(fn ($option) => [
                'body' => trim((string) ($option['body'] ?? '')),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
            ])
            ->filter(fn (array $option) => $option['body'] !== '')
            ->values();

        if ($options->count() < 2) {
            throw ValidationException::withMessages(['options' => ['السؤال محتاج اختيارين على الأقل.']]);
        }
        $correct = $options->where('is_correct', true)->count();
        if ($correct < 1) {
            throw ValidationException::withMessages(['options' => ['علّم اختيار واحد صحيح على الأقل.']]);
        }
        if ($type === 'single' && $correct !== 1) {
            throw ValidationException::withMessages(['options' => ['اختيار واحد صحيح بس في النوع ده. لو أكتر من إجابة، غيّر النوع.']]);
        }

        return $options->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function settingsFor(string $type, array $data): array
    {
        $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
        if ($type === 'multiple') {
            $scoring = (string) ($settings['scoring'] ?? 'all');

            return ['scoring' => in_array($scoring, ['all', 'partial', 'deduct'], true) ? $scoring : 'all'];
        }
        if ($type === 'true_false') {
            return ['correct' => (bool) ($settings['correct'] ?? false)];
        }
        if ($type === 'numerical') {
            if (! is_numeric($settings['answer'] ?? null)) {
                throw ValidationException::withMessages(['settings' => ['اكتب الإجابة الرقمية.']]);
            }

            return [
                'answer' => (string) $settings['answer'],
                'tolerance' => is_numeric($settings['tolerance'] ?? null) ? abs((float) $settings['tolerance']) : 0,
            ];
        }
        if ($type === 'fill_blank') {
            $answers = collect($settings['answers'] ?? [])->map(fn ($item) => trim((string) $item))->filter()->values();
            if ($answers->isEmpty()) {
                throw ValidationException::withMessages(['settings' => ['اكتب إجابة مقبولة واحدة على الأقل. سطر لكل إجابة.']]);
            }

            return ['answers' => $answers->all()];
        }
        if ($type === 'short') {
            return [
                'model_answer' => $this->blank($settings['model_answer'] ?? null),
            ];
        }
        if ($type === 'essay') {
            return [
                'model_answer' => $this->blank($settings['model_answer'] ?? null),
                'min_words' => max(0, (int) ($settings['min_words'] ?? 0)),
                'max_words' => max(0, (int) ($settings['max_words'] ?? 0)),
            ];
        }

        return [];
    }

    private function topicId(BankLesson $lesson, mixed $topicId): ?int
    {
        if (! $topicId) {
            return null;
        }
        $topic = BankTopic::query()->where('bank_lesson_id', $lesson->id)->find((int) $topicId);
        if (! $topic) {
            throw ValidationException::withMessages(['topic_id' => ['الموضوع مش من الدرس ده.']]);
        }

        return $topic->id;
    }

    /**
     * @param  list<int|string>  $tags
     * @return list<string>
     */
    private function tags(array $tags): array
    {
        return collect($tags)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $row
     */
    private function importRow(BankLesson $lesson, array $header, array $row): void
    {
        $cells = [];
        foreach ($header as $index => $key) {
            $cells[$key] = trim((string) ($row[$index] ?? ''));
        }
        $prompt = $cells['prompt'] ?? $cells['السؤال'] ?? '';
        if ($prompt === '') {
            return;
        }
        $type = $this->importType($cells['type'] ?? $cells['النوع'] ?? 'single');
        $choices = array_values(array_filter([
            $cells['a'] ?? '',
            $cells['b'] ?? '',
            $cells['c'] ?? '',
            $cells['d'] ?? '',
        ], fn ($body) => $body !== ''));
        $correct = mb_strtolower($cells['correct'] ?? $cells['الصحيح'] ?? '');
        $letters = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'أ' => 0, 'ب' => 1, 'ج' => 2, 'د' => 3];
        $marked = collect(preg_split('/[|,،]/u', $correct) ?: [])->map(fn ($item) => trim($item));
        $options = [];
        foreach ($choices as $index => $body) {
            $letter = array_search($index, $letters, true);
            $options[] = [
                'body' => $body,
                'is_correct' => $marked->contains(fn ($item) => $item === (string) $letter || $item === mb_strtolower($body) || (isset($letters[$item]) && $letters[$item] === $index)),
            ];
        }
        $settings = [];
        if ($type === 'true_false') {
            $settings['correct'] = in_array($correct, ['1', 'true', 'صح', 'ص'], true);
        } elseif ($type === 'numerical') {
            $settings['answer'] = $correct;
            $settings['tolerance'] = $cells['tolerance'] ?? 0;
        } elseif ($type === 'short') {
            $settings['answers'] = $marked->all();
        } elseif ($type === 'essay') {
            $settings['model_answer'] = $correct;
        } elseif ($type === 'multiple') {
            $settings['scoring'] = 'all';
        }

        $this->save($lesson, [
            'prompt' => $prompt,
            'type' => $type,
            'difficulty' => $this->importDifficulty($cells['difficulty'] ?? $cells['الصعوبة'] ?? '3'),
            'marks' => is_numeric($cells['marks'] ?? $cells['الدرجة'] ?? null) ? $cells['marks'] ?? $cells['الدرجة'] : 1,
            'explanation' => $cells['explanation'] ?? $cells['الشرح'] ?? null,
            'tags' => array_filter(array_map('trim', explode('|', $cells['tags'] ?? $cells['وسوم'] ?? ''))),
            'status' => 'published',
            'options' => $options,
            'settings' => $settings,
        ]);
    }

    private function importType(string $type): string
    {
        $type = mb_strtolower(trim($type));

        return match ($type) {
            'multiple', 'متعدد', 'اكثر' => 'multiple',
            'true_false', 'صح', 'صح وغلط' => 'true_false',
            'numerical', 'رقم' => 'numerical',
            'short', 'قصيرة', 'فراغ', 'fill_blank', 'املأ' => 'fill_blank',
            'essay', 'مقالي' => 'essay',
            default => 'single',
        };
    }

    private function importDifficulty(string $value): int
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'easy', 'سهل', '1', '2' => 2,
            'hard', 'صعب', '4', '5' => 4,
            'medium', 'متوسط', '3' => 3,
            default => is_numeric($value) ? max(1, min(5, (int) $value)) : 3,
        };
    }

    /**
     * @param  list<array{body: string, is_correct: bool}>  $options
     */
    private function writeOptions(Question $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            $question->options()->create([
                'body' => $option['body'],
                'is_correct' => $option['is_correct'],
                'sort' => $index,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function questionPayload(Question $question, bool $withAnswers): array
    {
        $question->loadMissing('options');
        $type = $question->type ?: 'single';
        $settings = $question->settings ?? [];
        $row = [
            'id' => $question->id,
            'prompt' => $question->prompt,
            'type' => $type,
            'multiple' => $type === 'multiple',
            'difficulty' => (int) ($question->difficulty ?: 3),
            'marks' => (float) ($question->marks ?: 1),
            'status' => $question->status ?: 'published',
            'tags' => $question->tags ?? [],
            'favorite' => (bool) $question->favorite,
            'hint' => $question->hint,
            'options' => $question->options->map(function (QuestionOption $option) use ($withAnswers) {
                $item = [
                    'id' => $option->id,
                    'body' => $option->body,
                ];
                if ($withAnswers) {
                    $item['is_correct'] = $option->is_correct;
                }

                return $item;
            })->values(),
        ];

        if ($withAnswers) {
            $row['explanation'] = $question->explanation;
            $row['negative_mark'] = $question->negative_mark;
            $row['settings'] = $settings;

            return $row;
        }

        if ($type === 'multiple') {
            $row['settings'] = ['scoring' => $settings['scoring'] ?? 'all'];
        } elseif ($type === 'essay') {
            $row['settings'] = [
                'min_words' => (int) ($settings['min_words'] ?? 0),
                'max_words' => (int) ($settings['max_words'] ?? 0),
            ];
        }

        return $row;
    }
}
