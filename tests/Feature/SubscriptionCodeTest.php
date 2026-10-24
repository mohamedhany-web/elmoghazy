<?php

namespace Tests\Feature;

use App\Models\AccessCode;
use App\Models\Activation;
use App\Models\Grade;
use App\Models\User;
use App\Services\Access\CodeIssuer;
use App\Services\Access\DeviceStamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sold_code_opens_one_month_on_one_device_then_disappears(): void
    {
        $grade = Grade::query()->create([
            'slug' => 'sec-1',
            'title' => 'الصف الأول',
            'subtitle' => 'sec',
            'accent' => 'cyan',
        ]);
        $other = Grade::query()->create([
            'slug' => 'sec-2',
            'title' => 'الصف الثاني',
            'subtitle' => 'sec',
            'accent' => 'cyan',
        ]);
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $grade->id]);
        $stranger = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $other->id]);

        $month = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/months', [
            'grade' => 'sec-1',
            'title' => 'شهر أكتوبر',
            'published' => true,
        ])->assertCreated();

        $issued = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/codes', [
            'type' => 'month',
            'study_month_id' => $month->json('id'),
            'count' => 3,
            'code_valid_days' => 10,
            'duration_days' => 7,
            'max_uses' => 50,
        ])->assertCreated();

        $issued->assertJsonCount(3, 'codes');
        $plain = $issued->json('codes.0');
        $this->assertSame(14, strlen($plain));
        $this->assertDatabaseMissing('access_codes', ['code_hash' => $plain]);
        $this->assertSame(1, AccessCode::query()->where('code_hash', CodeIssuer::hash($plain))->value('max_uses'));

        $token = bin2hex(random_bytes(32));
        $this->actingAs($stranger, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $token)
            ->postJson('/api/student/codes', ['code' => $plain])
            ->assertStatus(422);

        $this->assertSame(0, AccessCode::query()->where('code_hash', CodeIssuer::hash($plain))->value('uses'));

        $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $token)
            ->postJson('/api/student/codes', ['code' => $plain])
            ->assertOk();

        $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $token)
            ->postJson('/api/student/codes', ['code' => $plain])
            ->assertStatus(422);

        $board = $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $token)
            ->getJson('/api/student/board')
            ->assertOk();
        $board->assertJsonPath('subscribed', true);
        $board->assertJsonPath('activations.0.course', 'شهر أكتوبر');

        $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $token)
            ->getJson('/api/student/months')
            ->assertOk()
            ->assertJsonPath('0.title', 'شهر أكتوبر');

        $otherDevice = bin2hex(random_bytes(32));
        $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $otherDevice)
            ->getJson('/api/student/months')
            ->assertOk()
            ->assertJsonCount(0);
        $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $otherDevice)
            ->getJson('/api/student/board')
            ->assertJsonPath('subscribed', false);

        $this->travel(8)->days();
        $this->actingAs($student, 'sanctum')
            ->withCredentials()
            ->withUnencryptedCookie(DeviceStamp::COOKIE, $token)
            ->getJson('/api/student/months')
            ->assertJsonCount(0);

        $stored = Activation::query()->where('user_id', $student->id)->first();
        $this->assertNotNull($stored->device_hash);
        $this->assertNotSame($token, $stored->device_hash);
    }
}
