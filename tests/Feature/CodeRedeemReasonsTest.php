<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use App\Services\Access\DeviceStamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeRedeemReasonsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private int $monthId;

    protected function setUp(): void
    {
        parent::setUp();

        $grade = Grade::query()->create(['slug' => 'sec-1', 'title' => 'الصف الأول', 'subtitle' => 'sec', 'accent' => 'cyan']);
        Grade::query()->create(['slug' => 'sec-2', 'title' => 'الصف الثاني', 'subtitle' => 'sec', 'accent' => 'cyan']);
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->student = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $grade->id]);

        $this->monthId = $this->actingAs($this->teacher, 'sanctum')->postJson('/api/teacher/months', [
            'grade' => 'sec-1',
            'title' => 'شهر أكتوبر',
            'published' => true,
        ])->assertCreated()->json('id');
    }

    private function issue(int $validDays = 10): string
    {
        return $this->actingAs($this->teacher, 'sanctum')->postJson('/api/teacher/codes', [
            'type' => 'month',
            'study_month_id' => $this->monthId,
            'code_valid_days' => $validDays,
            'duration_days' => 30,
        ])->assertCreated()->json('codes.0');
    }

    private function redeem(User $student, string $code, string $device)
    {
        return $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $device)
            ->postJson('/api/student/codes', ['code' => $code]);
    }

    public function test_arabic_digits_and_missing_dashes_still_redeem(): void
    {
        $plain = $this->issue();
        $typed = strtolower(strtr(str_replace('-', ' ', $plain), [
            '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]));

        $this->redeem($this->student, $typed, bin2hex(random_bytes(32)))->assertOk();
    }

    public function test_each_failure_gets_its_own_message(): void
    {
        $device = bin2hex(random_bytes(32));

        $this->redeem($this->student, 'ABCD-EFGH-JKLM', $device)
            ->assertStatus(422)->assertJsonPath('errors.code.0', fn ($m) => str_contains($m, 'غير صحيح'));

        $plain = $this->issue();
        $this->redeem($this->student, $plain, $device)->assertOk();
        $this->redeem($this->student, $plain, $device)
            ->assertStatus(422)->assertJsonPath('errors.code.0', fn ($m) => str_contains($m, 'متفعّل على حسابك'));

        $other = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $this->student->grade_id]);
        $this->redeem($other, $plain, $device)
            ->assertStatus(422)->assertJsonPath('errors.code.0', fn ($m) => str_contains($m, 'حساب تاني'));

        $wrongGrade = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => Grade::query()->where('slug', 'sec-2')->value('id')]);
        $this->redeem($wrongGrade, $this->issue(), $device)
            ->assertStatus(422)->assertJsonPath('errors.code.0', fn ($m) => str_contains($m, 'صف دراسي'));

        $expiring = $this->issue(1);
        $this->travel(2)->days();
        $this->redeem($this->student, $expiring, $device)
            ->assertStatus(422)->assertJsonPath('errors.code.0', fn ($m) => str_contains($m, 'انتهت'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'code.rejected']);
    }

    public function test_teacher_reset_moves_subscription_to_the_next_device(): void
    {
        $phone = bin2hex(random_bytes(32));
        $laptop = bin2hex(random_bytes(32));
        $this->redeem($this->student, $this->issue(), $phone)->assertOk();

        $this->actingAs($this->student, 'sanctum')->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $laptop)
            ->getJson('/api/student/board')
            ->assertJsonPath('subscribed', false)
            ->assertJsonPath('device_locked', true);

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/teacher/students/{$this->student->id}/reset-device")
            ->assertOk()->assertJsonPath('activations', 1);

        $this->actingAs($this->student, 'sanctum')->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $laptop)
            ->getJson('/api/student/months')
            ->assertJsonCount(1);

        // اتربط باللابتوب، والموبايل القديم اتقفل
        $this->actingAs($this->student, 'sanctum')->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $phone)
            ->getJson('/api/student/months')
            ->assertJsonCount(0);
    }
}
