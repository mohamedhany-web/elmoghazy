<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\PurchaseRequest;
use App\Models\StudyMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MonthCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_lists_published_months_by_requests(): void
    {
        $grade = $this->grade();
        $quiet = $this->month($grade, 'شهر هادي', true);
        $busy = $this->month($grade, 'شهر أكتوبر', true);
        $this->month($grade, 'شهر مخفي', false);

        PurchaseRequest::query()->create([
            'study_month_id' => $busy->id,
            'name' => 'أحمد',
            'phone' => '01000000000',
            'status' => 'pending',
        ]);

        $this->getJson('/api/months')
            ->assertOk()
            ->assertJsonPath('0.title', 'شهر أكتوبر')
            ->assertJsonPath('0.sales_count', 1)
            ->assertJsonMissing(['title' => 'شهر مخفي'])
            ->assertJsonMissingPath('0.key');

        $this->assertNotSame($quiet->id, $busy->id);
        $this->getJson('/api/months')->assertJsonPath('0.has_cover', false);
    }

    public function test_cover_streams_from_the_platform_without_the_storage_key(): void
    {
        Storage::fake('r2');
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $grade = $this->grade();

        $this->actingAs($teacher, 'sanctum')->post('/api/teacher/months', [
            'grade' => $grade->slug,
            'title' => 'شهر بصورة',
            'published' => '1',
            'cover' => UploadedFile::fake()->image('thumb.png'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonMissingPath('key');

        $month = StudyMonth::query()->where('title', 'شهر بصورة')->first();
        $this->assertNotNull($month?->cover_media_id);

        $this->getJson('/api/months')
            ->assertOk()
            ->assertJsonFragment(['title' => 'شهر بصورة', 'has_cover' => true])
            ->assertJsonMissingPath('0.key');

        $this->get('/months/'.$month->id.'/cover')
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('cache-control', 'max-age=604800, public');

        $hidden = $this->month($grade, 'مخفي', false);
        $hidden->cover_media_id = $month->cover_media_id;
        $hidden->save();
        $this->get('/months/'.$hidden->id.'/cover')->assertNotFound();
    }

    public function test_month_page_hides_unpublished_and_accepts_a_request(): void
    {
        $grade = $this->grade();
        $month = $this->month($grade, 'شهر نوفمبر', true);
        $hidden = $this->month($grade, 'مسودة', false);
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'phone' => '01011111111',
            'grade_id' => $grade->id,
        ]);

        $this->getJson('/api/months/'.$hidden->id)->assertNotFound();

        $this->actingAs($student, 'sanctum')->postJson('/api/months/'.$month->id.'/requests', [
            'name' => 'أحمد تجربة',
            'phone' => '01012345678',
            'note' => 'عايز الشهر ده',
        ])->assertCreated();

        $this->assertDatabaseHas('purchase_requests', [
            'study_month_id' => $month->id,
            'user_id' => $student->id,
            'phone' => '01012345678',
            'status' => 'pending',
        ]);

        $this->postJson('/api/months/'.$month->id.'/requests', [
            'name' => 'أحمد تجربة',
            'phone' => '01012345678',
        ])->assertUnprocessable();
    }

    public function test_teacher_sees_the_request_and_closes_it(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $month = $this->month($this->grade(), 'شهر ديسمبر', true);
        $row = PurchaseRequest::query()->create([
            'study_month_id' => $month->id,
            'name' => 'منى',
            'phone' => '01123456789',
            'status' => 'pending',
        ]);

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/teacher/requests')
            ->assertOk()
            ->assertJsonFragment(['name' => 'منى', 'month' => 'شهر ديسمبر', 'status' => 'pending']);

        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/requests/'.$row->id.'/close')
            ->assertOk();

        $this->assertSame('done', $row->fresh()->status);
    }

    private function grade(): Grade
    {
        return Grade::query()->create([
            'slug' => 'stage-test',
            'title' => 'صف تجريبي',
            'subtitle' => 'وصف',
            'accent' => 'cyan',
            'sort' => 1,
        ]);
    }

    private function month(Grade $grade, string $title, bool $published): StudyMonth
    {
        return StudyMonth::query()->create([
            'grade_id' => $grade->id,
            'title' => $title,
            'description' => 'شرح الشهر',
            'sort' => 1,
            'published' => $published,
        ]);
    }
}
