<?php

namespace Tests\Feature;

use App\Models\Activation;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_builds_a_bank_and_a_permanent_exam_from_it(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $grade = $this->grade('أولى ثانوي');
        $other = $this->grade('تانية ثانوي');

        $unit = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/banks/'.$grade->id.'/units', ['title' => 'الحركة'])
            ->assertCreated();

        $lesson = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/bank-units/'.$unit->json('id').'/lessons', ['title' => 'السرعة'])
            ->assertCreated();

        $question = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
                'prompt' => 'السرعة هي؟',
                'options' => [
                    ['body' => 'المسافة على الزمن', 'is_correct' => true],
                    ['body' => 'الكتلة على الحجم', 'is_correct' => false],
                    ['body' => 'القوة على المساحة', 'is_correct' => false],
                ],
            ])->assertCreated();

        $foreign = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/banks/'.$other->id.'/units', ['title' => 'وحدة تانية'])
            ->assertCreated();
        $foreignLesson = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/bank-units/'.$foreign->json('id').'/lessons', ['title' => 'درس'])
            ->assertCreated();
        $foreignQuestion = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/bank-lessons/'.$foreignLesson->json('id').'/questions', [
                'prompt' => 'سؤال سنة تانية',
                'options' => [
                    ['body' => 'أ', 'is_correct' => true],
                    ['body' => 'ب', 'is_correct' => false],
                ],
            ])->assertCreated();

        $exam = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/exams', [
            'title' => 'امتحان الحركة',
            'grade_id' => $grade->id,
            'permanent' => true,
            'pass_percent' => 50,
        ])->assertCreated();

        $this->actingAs($teacher, 'sanctum')->putJson('/api/teacher/exams/'.$exam->json('id').'/questions', [
            'questions' => [
                ['id' => $question->json('id'), 'points' => 2],
                ['id' => $foreignQuestion->json('id'), 'points' => 1],
            ],
        ])->assertStatus(422);

        $this->actingAs($teacher, 'sanctum')->putJson('/api/teacher/exams/'.$exam->json('id').'/questions', [
            'questions' => [
                ['id' => $question->json('id'), 'points' => 2],
            ],
        ])->assertOk();

        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'grade_id' => $grade->id,
        ]);

        $this->actingAs($student, 'sanctum')->getJson('/api/student/exams')->assertOk()->assertJsonCount(0);

        $this->actingAs($teacher, 'sanctum')->patchJson('/api/teacher/exams/'.$exam->json('id'), [
            'published' => true,
        ])->assertOk();

        $this->actingAs($student, 'sanctum')->getJson('/api/student/exams')
            ->assertOk()
            ->assertJsonPath('0.permanent', true)
            ->assertJsonPath('0.locked', true);

        Activation::query()->create([
            'user_id' => $student->id,
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/start')->assertOk();

        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/save', [
            'answers' => [
                ['question_id' => $question->json('id'), 'option_ids' => [$this->correctOption($question->json('id'))]],
            ],
        ])->assertOk();

        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/submit', [
            'answers' => [
                ['question_id' => $question->json('id'), 'option_ids' => [$this->correctOption($question->json('id'))]],
            ],
        ])->assertOk()
            ->assertJsonPath('score', 2)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('passed', true);
    }

    public function test_typed_questions_grade_and_drafts_stay_out_of_exams(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $grade = $this->grade('أولى ثانوي');
        $unit = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/banks/'.$grade->id.'/units', ['title' => 'القياس'])->assertCreated();
        $lesson = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-units/'.$unit->json('id').'/lessons', ['title' => 'الكتلة'])->assertCreated();

        $number = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
            'prompt' => 'احسب الناتج',
            'type' => 'numerical',
            'difficulty' => 4,
            'marks' => 2,
            'settings' => ['answer' => '15', 'tolerance' => 0.1],
        ])->assertCreated();

        $draft = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
            'prompt' => 'لسه مسودة',
            'type' => 'true_false',
            'status' => 'draft',
            'settings' => ['correct' => true],
        ])->assertCreated();

        $imported = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/import', [
            'csv' => "prompt,type,a,b,c,d,correct,difficulty,marks\nالعجلة؟,single,السرعة,الكتلة,القوة,الشغل,A,easy,1\n",
        ])->assertOk()->json('count');
        $this->assertSame(1, $imported);

        $exam = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/exams', [
            'title' => 'قياس',
            'grade_id' => $grade->id,
            'permanent' => true,
        ])->assertCreated();

        $this->actingAs($teacher, 'sanctum')->putJson('/api/teacher/exams/'.$exam->json('id').'/questions', [
            'questions' => [['id' => $draft->json('id'), 'points' => 1]],
        ])->assertStatus(422);

        $this->actingAs($teacher, 'sanctum')->putJson('/api/teacher/exams/'.$exam->json('id').'/questions', [
            'questions' => [['id' => $number->json('id'), 'points' => 2]],
        ])->assertOk();

        $student = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $grade->id]);
        Activation::query()->create([
            'user_id' => $student->id,
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        $this->actingAs($teacher, 'sanctum')->patchJson('/api/teacher/exams/'.$exam->json('id'), ['published' => true])->assertOk();
        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/start')->assertOk();
        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/submit', [
            'answers' => [['question_id' => $number->json('id'), 'option_ids' => [], 'response' => '14.95']],
        ])->assertOk()->assertJsonPath('score', 2);
    }

    public function test_attempt_rules_follow_the_exam_contract(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $grade = $this->grade('أولى ثانوي');
        $unit = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/banks/'.$grade->id.'/units', ['title' => 'الشغل'])->assertCreated();
        $lesson = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-units/'.$unit->json('id').'/lessons', ['title' => 'القدرة'])->assertCreated();

        $choice = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
            'prompt' => 'وحدة القدرة؟',
            'type' => 'single',
            'options' => [
                ['body' => 'الواط', 'is_correct' => true],
                ['body' => 'النيوتن', 'is_correct' => false],
            ],
        ])->assertCreated();

        $blank = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
            'prompt' => 'اكتب وحدة الشغل',
            'type' => 'fill_blank',
            'settings' => ['answers' => ['جول', 'الجول']],
        ])->assertCreated();

        $essay = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
            'prompt' => 'اشرح الشغل',
            'type' => 'short',
            'settings' => ['model_answer' => 'القوة في الإزاحة'],
        ])->assertCreated();

        $spare = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/bank-lessons/'.$lesson->json('id').'/questions', [
            'prompt' => 'سؤال مش هيتحط',
            'options' => [
                ['body' => 'أ', 'is_correct' => true],
                ['body' => 'ب', 'is_correct' => false],
            ],
        ])->assertCreated();
        $this->actingAs($teacher, 'sanctum')->deleteJson('/api/teacher/questions/'.$spare->json('id'))->assertOk();

        $exam = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/exams', [
            'title' => 'امتحان الشغل',
            'grade_id' => $grade->id,
            'permanent' => true,
            'pass_percent' => 50,
            'attempts_allowed' => 1,
            'duration_minutes' => 20,
            'show_results' => false,
        ])->assertCreated();

        $this->actingAs($teacher, 'sanctum')->putJson('/api/teacher/exams/'.$exam->json('id').'/questions', [
            'questions' => [
                ['id' => $choice->json('id'), 'points' => 1],
                ['id' => $blank->json('id'), 'points' => 0.5],
                ['id' => $essay->json('id'), 'points' => 2],
            ],
        ])->assertOk();

        $this->actingAs($teacher, 'sanctum')->deleteJson('/api/teacher/questions/'.$choice->json('id'))->assertStatus(422);

        $this->actingAs($teacher, 'sanctum')->getJson('/api/teacher/exams/'.$exam->json('id'))
            ->assertOk()
            ->assertJsonPath('exam.total_marks', 3.5)
            ->assertJsonPath('exam.passing_marks', 1.75);

        $student = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $grade->id]);
        Activation::query()->create([
            'user_id' => $student->id,
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        $this->actingAs($teacher, 'sanctum')->patchJson('/api/teacher/exams/'.$exam->json('id'), ['published' => true])->assertOk();

        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/start')->assertOk();
        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/save', [
            'question_id' => $choice->json('id'),
            'answer' => 'الواط',
        ])->assertOk();
        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/submit', [
            'answers' => [
                ['question_id' => $choice->json('id'), 'answer' => 'الواط'],
                ['question_id' => $blank->json('id'), 'response' => 'الجول'],
                ['question_id' => $essay->json('id'), 'response' => 'شرح'],
            ],
        ])->assertOk()->assertJsonPath('hidden_result', true);

        $this->actingAs($student, 'sanctum')->postJson('/api/student/exams/'.$exam->json('id').'/start')->assertStatus(422);

        $attempt = \App\Models\ExamAttempt::query()->first();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame(1.5, (float) $attempt->score);
    }

    private function grade(string $title): Grade
    {
        return Grade::query()->create([
            'slug' => 'stage-'.str()->lower(str()->random(6)),
            'title' => $title,
            'subtitle' => '',
            'accent' => 'cyan',
            'sort' => 1,
        ]);
    }

    private function correctOption(int $questionId): int
    {
        return (int) \App\Models\QuestionOption::query()
            ->where('question_id', $questionId)
            ->where('is_correct', true)
            ->value('id');
    }
}
