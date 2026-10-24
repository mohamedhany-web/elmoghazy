<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_cannot_use_the_student_api_and_a_student_cannot_use_the_teacher_api(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        $this->actingAs($teacher, 'sanctum')->getJson('/api/student/board')->assertForbidden();
        $this->actingAs($teacher, 'sanctum')->getJson('/api/student/months')->assertForbidden();
        $this->actingAs($teacher, 'sanctum')->getJson('/api/teacher/summary')->assertOk();

        $this->actingAs($student, 'sanctum')->getJson('/api/teacher/summary')->assertForbidden();
        $this->actingAs($student, 'sanctum')->getJson('/api/student/board')->assertOk();
    }
}
