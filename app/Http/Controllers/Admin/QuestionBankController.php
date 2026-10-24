<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankLesson;
use App\Models\BankTopic;
use App\Models\BankUnit;
use App\Models\Grade;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Services\Exams\QuestionBankWriter;
use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    public function __construct(private readonly QuestionBankWriter $banks) {}

    public function index()
    {
        $grades = Grade::query()->orderBy('sort')->get();

        return response()->json($grades->map(function (Grade $grade) {
            $outline = QuestionBank::query()->where('grade_id', $grade->id)->exists()
                ? $this->banks->outline($grade)
                : ['counts' => ['units' => 0, 'lessons' => 0, 'questions' => 0]];

            return [
                'id' => $grade->id,
                'title' => $grade->title,
                'subtitle' => $grade->subtitle,
                'units' => $outline['counts']['units'],
                'lessons' => $outline['counts']['lessons'],
                'questions' => $outline['counts']['questions'],
                'tree' => $outline['units'] ?? [],
            ];
        }));
    }

    public function show(Grade $grade)
    {
        return response()->json($this->banks->outline($grade));
    }

    public function questions(Request $request, Grade $grade)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'unit' => ['nullable', 'integer'],
            'lesson' => ['nullable', 'integer'],
            'topic' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:20'],
            'difficulty' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($this->banks->browse($grade, $data));
    }

    public function storeUnit(Request $request, Grade $grade)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'اكتب اسم الوحدة.']);

        $unit = $this->banks->addUnit($grade, $data['title']);

        return response()->json(['id' => $unit->id], 201);
    }

    public function updateUnit(Request $request, BankUnit $unit)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'اكتب اسم الوحدة.']);
        $this->banks->renameUnit($unit, $data['title']);

        return response()->json(['ok' => true]);
    }

    public function destroyUnit(BankUnit $unit)
    {
        $this->banks->deleteUnit($unit);

        return response()->json(['ok' => true]);
    }

    public function storeLesson(Request $request, BankUnit $unit)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'اكتب اسم الدرس.']);
        $lesson = $this->banks->addLesson($unit, $data['title']);

        return response()->json(['id' => $lesson->id], 201);
    }

    public function updateLesson(Request $request, BankLesson $lesson)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'اكتب اسم الدرس.']);
        $this->banks->renameLesson($lesson, $data['title']);

        return response()->json(['ok' => true]);
    }

    public function destroyLesson(BankLesson $lesson)
    {
        $this->banks->deleteLesson($lesson);

        return response()->json(['ok' => true]);
    }

    public function storeQuestion(Request $request, BankLesson $lesson)
    {
        $question = $this->banks->save($lesson, $this->questionFields($request));

        return response()->json(['id' => $question->id], 201);
    }

    public function updateQuestion(Request $request, Question $question)
    {
        if ($request->exists('favorite') && ! $request->exists('prompt')) {
            $question->update(['favorite' => $request->boolean('favorite')]);

            return response()->json(['ok' => true]);
        }

        $question->loadMissing('lesson');
        $this->banks->save($question->lesson, $this->questionFields($request), $question);

        return response()->json(['ok' => true]);
    }

    public function destroyQuestion(Question $question)
    {
        $this->banks->deleteQuestion($question);

        return response()->json(['ok' => true]);
    }

    public function duplicateQuestion(Question $question)
    {
        $copy = $this->banks->duplicate($question);

        return response()->json(['id' => $copy->id], 201);
    }

    public function bulk(Request $request, Grade $grade)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:delete,archive,publish,draft,difficulty,tag,move'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'tag' => ['nullable', 'string', 'max:40'],
            'lesson_id' => ['nullable', 'integer'],
        ], [
            'ids.required' => 'اختار سؤال واحد على الأقل.',
        ]);

        $count = $this->banks->bulk($grade, $data['ids'], $data['action'], $data);

        return response()->json(['count' => $count]);
    }

    public function import(Request $request, BankLesson $lesson)
    {
        $data = $request->validate([
            'csv' => ['required', 'string', 'max:500000'],
        ], [
            'csv.required' => 'الصق الأسئلة أو ارفع الملف.',
        ]);

        return response()->json(['count' => $this->banks->importCsv($lesson, $data['csv'])]);
    }

    public function storeTopic(Request $request, BankLesson $lesson)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'اكتب اسم الموضوع.']);

        $topic = $this->banks->addTopic($lesson, $data['title']);

        return response()->json(['id' => $topic->id], 201);
    }

    public function updateTopic(Request $request, BankTopic $topic)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'اكتب اسم الموضوع.']);
        $this->banks->renameTopic($topic, $data['title']);

        return response()->json(['ok' => true]);
    }

    public function destroyTopic(BankTopic $topic)
    {
        $this->banks->deleteTopic($topic);

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function questionFields(Request $request): array
    {
        return $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'in:single,multiple,multiple_choice,true_false,fill_blank,numerical,short,essay'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'marks' => ['nullable', 'numeric', 'min:0.25', 'max:100'],
            'negative_mark' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'hint' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:40'],
            'topic_id' => ['nullable', 'integer'],
            'favorite' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'array', 'max:12'],
            'options.*.body' => ['nullable', 'string', 'max:1000'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ], [
            'prompt.required' => 'اكتب نص السؤال.',
        ]);
    }
}
