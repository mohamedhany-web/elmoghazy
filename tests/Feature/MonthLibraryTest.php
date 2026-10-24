<?php

namespace Tests\Feature;

use App\Models\Activation;
use App\Models\Grade;
use App\Models\Media;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MonthLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_stays_visible_when_locked_and_the_storage_link_never_leaves_the_server(): void
    {
        Storage::fake('r2');
        $grade = $this->grade('prep');
        $other = $this->grade('sec-1');
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $grade->id]);
        $stranger = User::factory()->create(['role' => 'student', 'status' => 'active', 'grade_id' => $other->id]);

        $month = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/months', [
            'grade' => 'prep',
            'title' => 'شهر 10',
            'description' => 'الحركة',
            'published' => true,
            'locks_at' => now()->addMonth()->toDateTimeString(),
        ])->assertCreated();

        $unit = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/months/'.$month->json('id').'/units', [
            'title' => 'الوحدة الأولى',
            'description' => 'السرعة',
            'published' => true,
        ])->assertCreated();

        $created = $this->actingAs($teacher, 'sanctum')->post('/api/teacher/units/'.$unit->json('id').'/videos', [
            'title' => 'شرح السرعة',
            'published' => '1',
            'video' => $this->videoFile(),
            'attachments' => [$this->pdfFile()],
        ], ['Accept' => 'application/json'])->assertCreated();

        $video = Video::query()->findOrFail($created->json('id'));
        $key = Media::query()->findOrFail($video->media_id)->key;

        Activation::query()->create([
            'user_id' => $student->id,
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $open = $this->actingAs($student, 'sanctum')->getJson('/api/student/months');
        $open->assertOk();
        $open->assertJsonPath('0.units.0.videos.0.locked', false);
        $open->assertJsonPath('0.units.0.videos.0.title', 'شرح السرعة');
        $this->assertStringNotContainsString($key, $open->getContent());
        $this->assertStringNotContainsString('r2.cloudflarestorage.com', $open->getContent());

        $ticket = $this->actingAs($student, 'sanctum')->postJson('/api/student/videos/'.$video->id.'/ticket');
        $ticket->assertOk();
        $token = $ticket->json('token');
        $this->assertNotEmpty($token);

        $this->actingAs($student, 'sanctum')->get('/api/student/videos/'.$video->id.'/stream')->assertForbidden();

        $stream = $this->actingAs($student, 'sanctum')->get('/api/student/videos/'.$video->id.'/stream?t='.$token);
        $stream->assertRedirect();
        $this->assertNotEmpty($stream->headers->get('Location'));

        $this->actingAs($stranger, 'sanctum')->getJson('/api/student/months')->assertJsonCount(0);
        $this->actingAs($stranger, 'sanctum')->get('/api/student/videos/'.$video->id.'/stream?t='.$token)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->get('/api/student/videos/'.$video->id.'/stream')->assertForbidden();
        $this->get('/api/student/videos/'.$video->id.'/stream?t='.$token)->assertRedirect();

        $video->unit->month->update(['locks_at' => now()->subMinute()]);

        $locked = $this->actingAs($student, 'sanctum')->getJson('/api/student/months');
        $locked->assertJsonPath('0.title', 'شهر 10');
        $locked->assertJsonPath('0.units.0.videos.0.id', $video->id);
        $locked->assertJsonPath('0.units.0.videos.0.locked', true);
        $this->actingAs($student, 'sanctum')->get('/api/student/videos/'.$video->id.'/stream?t='.$token)->assertForbidden();
        $this->actingAs($teacher, 'sanctum')->get('/api/teacher/videos/'.$video->id.'/stream')->assertSuccessful();
    }

    public function test_direct_upload_ticket_hides_the_storage_key_and_creates_the_video(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->buildTemporaryUploadUrlsUsing(fn () => [
            'url' => 'https://upload.test/put',
            'headers' => [],
        ]);
        $this->grade('prep');
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $month = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/months', [
            'grade' => 'prep',
            'title' => 'شهر 10',
            'published' => true,
        ])->assertCreated();
        $unit = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/months/'.$month->json('id').'/units', [
            'title' => 'الوحدة',
            'published' => true,
        ])->assertCreated();

        $ticket = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/uploads', [
            'purpose' => 'video',
            'name' => 'clip.mp4',
            'mime' => 'video/mp4',
            'size' => 32,
        ])->assertOk();

        $ticket->assertJsonStructure(['token', 'url', 'content_type']);
        $this->assertSame(['content_type', 'token', 'url'], collect($ticket->json())->keys()->sort()->values()->all());
        $this->assertSame('https://upload.test/put', $ticket->json('url'));

        $pending = Cache::get('media-upload:'.$ticket->json('token'));
        Storage::disk('r2')->put($pending['key'], $this->videoBytes());

        $created = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/units/'.$unit->json('id').'/videos', [
            'ticket' => $ticket->json('token'),
            'title' => 'شرح السرعة',
            'published' => true,
        ])->assertCreated();

        $video = Video::query()->findOrFail($created->json('id'));
        $this->assertSame('شرح السرعة', $video->title);
        $this->assertNull(Cache::get('media-upload:'.$ticket->json('token')));
    }

    public function test_chunked_upload_is_saved_on_the_disk_without_returning_the_key(): void
    {
        Storage::fake('r2');
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $uploadId = (string) \Illuminate\Support\Str::uuid();
        $bytes = $this->videoBytes();

        $this->actingAs($teacher, 'sanctum')->post('/api/teacher/uploads/parts', [
            'upload_id' => $uploadId,
            'index' => 0,
            'total' => 1,
            'size' => strlen($bytes),
            'name' => 'clip.mp4',
            'mime' => 'video/mp4',
            'file' => $this->videoFile(),
        ], ['Accept' => 'application/json'])->assertOk();

        $finished = $this->actingAs($teacher, 'sanctum')->postJson('/api/teacher/uploads/parts/finish', [
            'upload_id' => $uploadId,
        ])->assertOk();

        $finished->assertJsonStructure(['media_id']);
        $this->assertSame(['media_id'], array_keys($finished->json()));
        $media = Media::query()->findOrFail($finished->json('media_id'));
        Storage::disk('r2')->assertExists($media->key);
        $this->assertSame($bytes, Storage::disk('r2')->get($media->key));
    }

    private function grade(string $slug): Grade
    {
        return Grade::query()->create([
            'slug' => $slug,
            'title' => $slug,
            'subtitle' => $slug,
            'accent' => 'cyan',
        ]);
    }

    private function videoFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'vid');
        file_put_contents($path, $this->videoBytes());

        return new UploadedFile($path, 'clip.mp4', 'video/mp4', null, true);
    }

    private function pdfFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

        return new UploadedFile($path, 'sheet.pdf', 'application/pdf', null, true);
    }

    private function videoBytes(): string
    {
        return hex2bin('000000186674797069736f6d0000000069736f6d6d703431');
    }
}
