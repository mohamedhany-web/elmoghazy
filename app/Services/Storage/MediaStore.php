<?php

namespace App\Services\Storage;

use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaStore
{
    public function store(UploadedFile $file, string $purpose, User $actor): Media
    {
        $purpose = $this->purpose($purpose);
        $mime = (string) $file->getMimeType();
        $extension = config('media.mimes')[$mime] ?? null;

        if (! is_string($extension)) {
            throw ValidationException::withMessages([
                'file' => ['نوع الملف مش مسموح.'],
            ]);
        }

        $this->assertSize((int) $file->getSize());

        $disk = (string) config('media.disk');
        $directory = $purpose.'/'.now()->format('Y/m');
        $name = (string) Str::ulid().'.'.$extension;

        try {
            $key = Storage::disk($disk)->putFileAs($directory, $file, $name, [
                'visibility' => 'private',
                'ContentType' => $mime,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $key = false;
        }

        if (! is_string($key) || $key === '') {
            throw ValidationException::withMessages([
                'file' => ['تعذر حفظ الملف. راجع إعدادات التخزين.'],
            ]);
        }

        return $this->record($actor, $disk, $key, $purpose, $mime, (int) ($file->getSize() ?: 0), $this->originalName($file));
    }

    private function record(User $actor, string $disk, string $key, string $purpose, string $mime, int $size, string $name): Media
    {
        try {
            $media = Media::query()->create([
                'disk' => $disk,
                'key' => $key,
                'purpose' => $purpose,
                'mime' => $mime,
                'size' => $size,
                'original_name' => $name,
                'uploaded_by' => $actor->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($key);
            throw $exception;
        }

        $this->log($actor, 'media.stored', $media, [
            'purpose' => $purpose,
            'mime' => $mime,
            'size' => $media->size,
        ]);

        return $media;
    }

    /**
     * تذكرة رفع مباشر إلى الدلو. الرد فيه رابط مؤقت للرفع فقط، من غير مفتاح تخزين.
     *
     * @return array{token: string, url: string, content_type: string}
     */
    public function reserve(User $actor, string $purpose, string $name, string $mime, int $size): array
    {
        $purpose = $this->purpose($purpose);
        $mime = $this->mime($mime, $name);
        $this->assertSize($size);

        $disk = (string) config('media.disk');
        $extension = config('media.mimes')[$mime];
        $key = $purpose.'/'.now()->format('Y/m').'/'.Str::ulid().'.'.$extension;
        $this->allowBrowserUpload($disk);

        $upload = Storage::disk($disk)->temporaryUploadUrl($key, now()->addMinutes(20), [
            'ContentType' => $mime,
        ]);

        $token = Str::random(40);
        Cache::put($this->ticketKey($token), [
            'disk' => $disk,
            'key' => $key,
            'purpose' => $purpose,
            'mime' => $mime,
            'size' => $size,
            'original_name' => $this->plainName($name),
            'actor_id' => $actor->id,
        ], now()->addMinutes(20));

        return [
            'token' => $token,
            'url' => (string) ($upload['url'] ?? ''),
            'content_type' => $mime,
        ];
    }

    public function writePart(User $actor, string $uploadId, int $index, int $total, int $size, string $name, string $mime, UploadedFile $chunk): void
    {
        $this->assertPart($uploadId, $index, $total, $size);
        $dir = $this->chunkDir($actor, $uploadId);
        File::ensureDirectoryExists($dir);
        $meta = [
            'total' => $total,
            'size' => $size,
            'name' => $this->plainName($name),
            'mime' => $this->mime($mime, $name),
        ];
        $metaPath = $dir.'/meta.json';
        if (is_file($metaPath)) {
            $existing = json_decode((string) file_get_contents($metaPath), true);
            if (! is_array($existing) || ($existing['total'] ?? null) !== $total || ($existing['size'] ?? null) !== $size) {
                throw ValidationException::withMessages([
                    'file' => ['جزء الرفع غير صالح.'],
                ]);
            }
        } else {
            file_put_contents($metaPath, json_encode($meta));
        }

        $chunk->move($dir, (string) $index);
    }

    public function assemble(User $actor, string $uploadId): Media
    {
        if (! Str::isUuid($uploadId)) {
            throw ValidationException::withMessages([
                'file' => ['جزء الرفع غير صالح.'],
            ]);
        }

        $dir = $this->chunkDir($actor, $uploadId);
        $meta = json_decode((string) @file_get_contents($dir.'/meta.json'), true);
        if (! is_array($meta)) {
            throw ValidationException::withMessages([
                'file' => ['انتهت مهلة الرفع. ابدأ من جديد.'],
            ]);
        }

        $out = tempnam(sys_get_temp_dir(), 'up');
        $target = fopen($out, 'wb');
        try {
            for ($index = 0; $index < (int) $meta['total']; $index++) {
                $part = $dir.'/'.$index;
                if (! is_file($part)) {
                    throw ValidationException::withMessages([
                        'file' => ['جزء من الفيديو ناقص. ابدأ الرفع من جديد.'],
                    ]);
                }
                $source = fopen($part, 'rb');
                stream_copy_to_stream($source, $target);
                fclose($source);
            }
        } catch (Throwable $exception) {
            fclose($target);
            @unlink($out);
            throw $exception;
        }
        fclose($target);

        $bytes = (int) filesize($out);
        $this->assertSize($bytes);
        $disk = (string) config('media.disk');
        $mime = (string) $meta['mime'];
        $extension = config('media.mimes')[$mime];
        $key = 'video/'.now()->format('Y/m').'/'.Str::ulid().'.'.$extension;
        $stream = fopen($out, 'rb');

        try {
            $stored = Storage::disk($disk)->put($key, $stream, [
                'visibility' => 'private',
                'ContentType' => $mime,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $stored = false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($out);
            File::deleteDirectory($dir);
        }

        if ($stored !== true) {
            throw ValidationException::withMessages([
                'file' => ['تعذر حفظ الملف. راجع إعدادات التخزين.'],
            ]);
        }

        return $this->record($actor, $disk, $key, 'video', $mime, $bytes, (string) $meta['name']);
    }

    public function claim(User $actor, string $token): Media
    {
        $pending = Cache::pull($this->ticketKey($token));
        if (! is_array($pending) || (int) ($pending['actor_id'] ?? 0) !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'ticket' => ['انتهت مهلة الرفع. ابدأ من جديد.'],
            ]);
        }

        $disk = Storage::disk((string) $pending['disk']);
        $key = (string) $pending['key'];
        if (! $disk->exists($key)) {
            throw ValidationException::withMessages([
                'ticket' => ['الفيديو ما وصلش لكلاود فلير.'],
            ]);
        }

        $size = (int) $disk->size($key);
        if ($size < 1) {
            $disk->delete($key);
            throw ValidationException::withMessages([
                'ticket' => ['الفيديو ما وصلش لكلاود فلير.'],
            ]);
        }

        return $this->record($actor, (string) $pending['disk'], $key, (string) $pending['purpose'], (string) $pending['mime'], $size, (string) $pending['original_name']);
    }

    public function temporaryUrl(Media $media, ?int $minutes = null): string
    {
        $minutes = $minutes ?? (int) config('media.temporary_minutes');
        $minutes = max(1, min(720, $minutes));

        return Storage::disk($media->disk)->temporaryUrl(
            $media->key,
            now()->addMinutes($minutes),
        );
    }

    public function delete(Media $media, User $actor): void
    {
        try {
            Storage::disk($media->disk)->delete($media->key);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => ['تعذر حذف الملف. راجع إعدادات التخزين.'],
            ]);
        }

        $this->log($actor, 'media.deleted', $media, [
            'purpose' => $media->purpose,
        ]);

        $media->delete();
    }

    private function purpose(string $purpose): string
    {
        $purpose = strtolower(trim($purpose));
        $allowed = config('media.purposes');

        if (! is_array($allowed) || ! in_array($purpose, $allowed, true)) {
            throw ValidationException::withMessages([
                'purpose' => ['نوع التخزين غير معروف.'],
            ]);
        }

        return $purpose;
    }

    private function assertSize(int $size): void
    {
        $maxKilobytes = (int) config('media.max_kilobytes');
        if ($size < 1 || $size > $maxKilobytes * 1024) {
            throw ValidationException::withMessages([
                'file' => ['حجم الملف أكبر من المسموح.'],
            ]);
        }
    }

    private function mime(string $mime, string $name): string
    {
        $mime = strtolower(trim($mime));
        $map = config('media.mimes');
        if (is_array($map) && isset($map[$mime])) {
            return $mime;
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $fromName = is_array($map) ? array_search($extension, $map, true) : false;
        if (is_string($fromName)) {
            return $fromName;
        }

        throw ValidationException::withMessages([
            'file' => ['نوع الملف مش مسموح.'],
        ]);
    }

    private function plainName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = trim($name);

        return $name === '' || $name === '.' || $name === '..' ? 'file' : Str::limit($name, 180, '');
    }

    private function ticketKey(string $token): string
    {
        return 'media-upload:'.$token;
    }

    private function assertPart(string $uploadId, int $index, int $total, int $size): void
    {
        $this->assertSize($size);
        $maxParts = max(1, (int) config('media.max_parts', 512));
        if (! Str::isUuid($uploadId) || $total < 1 || $total > $maxParts || $index < 0 || $index >= $total) {
            throw ValidationException::withMessages([
                'file' => ['جزء الرفع غير صالح.'],
            ]);
        }
    }

    private function chunkDir(User $actor, string $uploadId): string
    {
        return storage_path('app/chunks/'.$actor->id.'/'.$uploadId);
    }

    private function allowBrowserUpload(string $disk): void
    {
        if (Cache::get('media.browser-upload') === $disk) {
            return;
        }

        try {
            $filesystem = Storage::disk($disk);
            if (! method_exists($filesystem, 'getClient')) {
                return;
            }

            $origins = array_values(array_unique(array_filter([
                rtrim((string) config('app.url'), '/'),
                'http://127.0.0.1:8000',
                'http://localhost:8000',
            ])));
            $filesystem->getClient()->putBucketCors([
                'Bucket' => config('filesystems.disks.'.$disk.'.bucket'),
                'CORSConfiguration' => [
                    'CORSRules' => [[
                        'AllowedHeaders' => ['*'],
                        'AllowedMethods' => ['PUT', 'GET', 'HEAD'],
                        'AllowedOrigins' => $origins,
                        'ExposeHeaders' => ['ETag'],
                        'MaxAgeSeconds' => 3600,
                    ]],
                ],
            ]);
            Cache::put('media.browser-upload', $disk, now()->addDay());
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function originalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            return 'file';
        }

        return Str::limit($name, 180, '');
    }

    private function log(User $actor, string $action, Media $media, array $meta): void
    {
        ActivityLog::query()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $media::class,
            'subject_id' => $media->id,
            'meta' => $meta,
        ]);
    }
}
