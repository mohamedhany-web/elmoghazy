<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_stores_a_private_image_without_exposing_the_key(): void
    {
        Storage::fake('r2');
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $response = $this->actingAs($teacher, 'sanctum')->post('/api/teacher/media', [
            'purpose' => 'cover',
            'file' => UploadedFile::fake()->image('cover.png'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();
        $response->assertJsonMissingPath('key');
        $response->assertJsonMissingPath('disk');
        $response->assertJsonPath('purpose', 'cover');
        $response->assertJsonPath('mime', 'image/png');

        $media = Media::query()->first();
        $this->assertNotNull($media);
        Storage::disk('r2')->assertExists($media->key);
        $this->assertSame('private', Storage::disk('r2')->getVisibility($media->key));
        $this->assertStringStartsWith('cover/', $media->key);
    }

    public function test_student_cannot_store_media(): void
    {
        Storage::fake('r2');
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        $this->actingAs($student, 'sanctum')->post('/api/teacher/media', [
            'purpose' => 'cover',
            'file' => UploadedFile::fake()->image('cover.png'),
        ], [
            'Accept' => 'application/json',
        ])->assertForbidden();

        $this->assertSame(0, Media::query()->count());
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('r2');
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->actingAs($teacher, 'sanctum')->post('/api/teacher/media', [
            'purpose' => 'attachment',
            'file' => UploadedFile::fake()->create('page.html', 4, 'text/html'),
        ], [
            'Accept' => 'application/json',
        ])->assertUnprocessable();

        Storage::disk('r2')->assertDirectoryEmpty('/');
    }

    public function test_teacher_can_delete_a_stored_file(): void
    {
        Storage::fake('r2');
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $created = $this->actingAs($teacher, 'sanctum')->post('/api/teacher/media', [
            'purpose' => 'lesson',
            'file' => UploadedFile::fake()->image('board.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $id = $created->json('id');
        $key = Media::query()->findOrFail($id)->key;

        $this->actingAs($teacher, 'sanctum')
            ->deleteJson('/api/teacher/media/'.$id)
            ->assertOk();

        $this->assertNull(Media::query()->find($id));
        Storage::disk('r2')->assertMissing($key);
    }
}
