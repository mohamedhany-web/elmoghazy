<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherGradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_add_and_rename_a_stage(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $created = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/grades', [
            'title' => 'صف تمهيدي',
            'subtitle' => 'قبل الإعدادي',
        ])->assertCreated();

        $this->assertNotSame('', $created->json('slug'));

        $this->actingAs($teacher, 'sanctum')->patchJson('/api/teacher/grades/'.$created->json('id'), [
            'title' => 'تمهيدي',
            'subtitle' => 'مرحلة جديدة',
        ])->assertOk();

        $this->getJson('/api/grades')
            ->assertOk()
            ->assertJsonFragment(['title' => 'تمهيدي', 'subtitle' => 'مرحلة جديدة']);
    }

    public function test_stage_with_a_student_cannot_be_deleted(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $grade = Grade::query()->create([
            'slug' => 'custom',
            'title' => 'مرحلة خاصة',
            'subtitle' => 'وصف',
            'accent' => 'cyan',
            'sort' => 1,
        ]);
        User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'grade_id' => $grade->id,
        ]);

        $this->actingAs($teacher, 'sanctum')
            ->deleteJson('/api/teacher/grades/'.$grade->id)
            ->assertUnprocessable();

        $this->assertNotNull(Grade::query()->find($grade->id));
    }

    public function test_student_cannot_manage_stages(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        $this->actingAs($student, 'sanctum')->postJson('/api/teacher/grades', [
            'title' => 'مرحلة',
        ])->assertForbidden();
    }
}
