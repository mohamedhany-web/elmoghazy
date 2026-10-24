<?php

namespace App\Services\Library;

use App\Models\Media;
use App\Models\StudyMonth;
use App\Models\Unit;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoFile;
use App\Services\Storage\MediaStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class LibraryWriter
{
    public function __construct(private readonly MediaStore $media) {}

    public function addVideo(Unit $unit, User $actor, UploadedFile $file, array $fields, array $attachments): Video
    {
        $videoMedia = $this->media->store($file, 'video', $actor);
        $stored = [];

        try {
            foreach ($attachments as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $stored[] = $this->media->store($attachment, 'attachment', $actor);
                }
            }

            return DB::transaction(function () use ($unit, $fields, $videoMedia, $stored) {
                $video = $unit->videos()->create([
                    'media_id' => $videoMedia->id,
                    'title' => $fields['title'],
                    'description' => $fields['description'] ?? null,
                    'sort' => (int) $unit->videos()->max('sort') + 1,
                    'published' => (bool) ($fields['published'] ?? false),
                    'locks_at' => $fields['locks_at'] ?? null,
                ]);

                foreach ($stored as $item) {
                    $video->files()->create(['media_id' => $item->id]);
                }

                return $video->load('files.media');
            });
        } catch (Throwable $exception) {
            $this->drop($videoMedia, $actor);
            foreach ($stored as $item) {
                $this->drop($item, $actor);
            }

            throw $exception;
        }
    }

    public function deleteMonth(StudyMonth $month, User $actor): void
    {
        $month->load('units.videos.files.media', 'units.videos.media', 'cover');
        foreach ($month->units as $unit) {
            $this->deleteUnit($unit, $actor);
        }
        $cover = $month->cover;
        $month->delete();
        if ($cover) {
            $this->drop($cover, $actor);
        }
    }

    public function deleteUnit(Unit $unit, User $actor): void
    {
        $unit->load('videos.files.media', 'videos.media');
        foreach ($unit->videos as $video) {
            $this->deleteVideo($video, $actor);
        }
        $unit->delete();
    }

    /**
     * @param  array{title?: string, description?: ?string, published?: bool, locks_at?: mixed}  $fields
     */
    public function updateVideo(Video $video, array $fields): void
    {
        $video->update([
            'title' => $fields['title'] ?? $video->title,
            'description' => array_key_exists('description', $fields) ? $fields['description'] : $video->description,
            'published' => array_key_exists('published', $fields) ? (bool) $fields['published'] : $video->published,
            'locks_at' => array_key_exists('locks_at', $fields) ? $fields['locks_at'] : $video->locks_at,
            'sort' => array_key_exists('sort', $fields) ? (int) $fields['sort'] : $video->sort,
        ]);
    }

    public function addPreparedVideo(Unit $unit, User $actor, Media $media, array $fields, array $attachments): Video
    {
        $stored = [];

        try {
            foreach ($attachments as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $stored[] = $this->media->store($attachment, 'attachment', $actor);
                }
            }

            return DB::transaction(function () use ($unit, $fields, $media, $stored) {
                $video = $unit->videos()->create([
                    'media_id' => $media->id,
                    'title' => $fields['title'],
                    'description' => $fields['description'] ?? null,
                    'sort' => (int) $unit->videos()->max('sort') + 1,
                    'published' => (bool) ($fields['published'] ?? false),
                    'locks_at' => $fields['locks_at'] ?? null,
                ]);

                foreach ($stored as $item) {
                    $video->files()->create(['media_id' => $item->id]);
                }

                return $video->load('files.media');
            });
        } catch (Throwable $exception) {
            $this->drop($media, $actor);
            foreach ($stored as $item) {
                $this->drop($item, $actor);
            }

            throw $exception;
        }
    }

    public function replaceFromMedia(Video $video, User $actor, Media $media): void
    {
        $previous = $video->media;
        $video->update(['media_id' => $media->id]);
        if ($previous) {
            $this->drop($previous, $actor);
        }
    }

    public function replaceVideo(Video $video, User $actor, UploadedFile $file): void
    {
        $next = $this->media->store($file, 'video', $actor);
        $previous = $video->media;
        $video->update(['media_id' => $next->id]);
        if ($previous) {
            $this->drop($previous, $actor);
        }
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function addAttachments(Video $video, User $actor, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if ($attachment instanceof UploadedFile) {
                $stored = $this->media->store($attachment, 'attachment', $actor);
                $video->files()->create(['media_id' => $stored->id]);
            }
        }
    }

    public function deleteFile(VideoFile $file, User $actor): void
    {
        $file->load('media');
        $media = $file->media;
        $file->delete();
        if ($media) {
            $this->drop($media, $actor);
        }
    }

    public function deleteVideo(Video $video, User $actor): void
    {
        $video->load('files.media', 'media');
        $files = $video->files->pluck('media')->filter()->values();
        $media = $video->media;

        DB::transaction(function () use ($video) {
            $video->files()->delete();
            $video->delete();
        });

        foreach ($files as $file) {
            $this->drop($file, $actor);
        }

        if ($media) {
            $this->drop($media, $actor);
        }
    }

    public function nextSort(StudyMonth|Unit $parent): int
    {
        $relation = $parent instanceof StudyMonth ? $parent->units() : $parent->videos();

        return (int) $relation->max('sort') + 1;
    }

    private function drop($media, User $actor): void
    {
        try {
            $this->media->delete($media, $actor);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
