<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Media;
use App\Models\StudyMonth;
use App\Models\Unit;
use App\Models\Video;
use App\Models\VideoFile;
use App\Services\Library\LibraryWriter;
use App\Services\Storage\MediaStore;
use App\Services\Storage\CoverPreview;
use App\Services\Storage\PrivateStream;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class LibraryController extends Controller
{
    public function __construct(
        private readonly LibraryWriter $library,
        private readonly PrivateStream $stream,
        private readonly CoverPreview $covers,
        private readonly MediaStore $media,
    ) {}

    public function index()
    {
        $rows = StudyMonth::query()->with('grade:id,title,slug')->withCount(['units', 'videos'])->orderBy('sort')->get();

        return response()->json($rows->map(fn (StudyMonth $month) => [
            'id' => $month->id,
            'title' => $month->title,
            'description' => $month->description,
            'grade_id' => $month->grade_id,
            'grade' => $month->grade?->title,
            'grade_slug' => $month->grade?->slug,
            'has_cover' => (bool) $month->cover_media_id,
            'published' => $month->published,
            'locks_at' => $month->locks_at,
            'unit_count' => $month->units_count,
            'video_count' => $month->videos_count,
        ]));
    }

    public function show(StudyMonth $month)
    {
        $month->load('grade:id,title,slug', 'units.videos.files.media');

        return response()->json([
            'id' => $month->id,
            'title' => $month->title,
            'description' => $month->description,
            'grade_id' => $month->grade_id,
            'grade' => $month->grade?->title,
            'grade_slug' => $month->grade?->slug,
            'has_cover' => (bool) $month->cover_media_id,
            'published' => $month->published,
            'locks_at' => $month->locks_at,
            'units' => $month->units->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'title' => $unit->title,
                'description' => $unit->description,
                'published' => $unit->published,
                'locks_at' => $unit->locks_at,
                'videos' => $unit->videos->map(fn (Video $video) => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'published' => $video->published,
                    'locks_at' => $video->locks_at,
                    'effective_lock' => $video->locks_at ?? $unit->locks_at ?? $month->locks_at,
                    'files' => $video->files->map(fn (VideoFile $file) => [
                        'id' => $file->id,
                        'name' => $file->media?->original_name,
                    ]),
                ]),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->monthData($request, true);
        $month = StudyMonth::query()->create([
            'grade_id' => $data['grade_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort' => (int) StudyMonth::query()->max('sort') + 1,
            'published' => (bool) ($data['published'] ?? false),
            'locks_at' => $data['locks_at'] ?? null,
        ]);
        $this->attachCover($request, $month);

        return response()->json(['id' => $month->id], 201);
    }

    public function update(Request $request, StudyMonth $month)
    {
        $data = $this->monthData($request, false);
        $month->update($data);
        $this->attachCover($request, $month);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, StudyMonth $month)
    {
        $this->library->deleteMonth($month, $request->user());

        return response()->json(['ok' => true]);
    }

    public function storeUnit(Request $request, StudyMonth $month)
    {
        $data = $this->unitData($request, true);
        $unit = $month->units()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort' => $this->library->nextSort($month),
            'published' => (bool) ($data['published'] ?? false),
            'locks_at' => $data['locks_at'] ?? null,
        ]);

        return response()->json(['id' => $unit->id], 201);
    }

    public function updateUnit(Request $request, Unit $unit)
    {
        $unit->update($this->unitData($request, false));

        return response()->json(['ok' => true]);
    }

    public function destroyUnit(Request $request, Unit $unit)
    {
        $this->library->deleteUnit($unit, $request->user());

        return response()->json(['ok' => true]);
    }

    public function reserveUpload(Request $request)
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', 'in:video'],
            'name' => ['required', 'string', 'max:180'],
            'mime' => ['nullable', 'string', 'max:120'],
            'size' => ['required', 'integer', 'min:1'],
        ], [
            'name.required' => 'اختار ملف الفيديو.',
            'size.required' => 'اختار ملف الفيديو.',
        ]);

        return response()->json($this->media->reserve(
            $request->user(),
            $data['purpose'],
            $data['name'],
            (string) ($data['mime'] ?? ''),
            (int) $data['size'],
        ));
    }

    public function storePart(Request $request)
    {
        $maxParts = max(1, (int) config('media.max_parts', 512));
        $partKilobytes = max(1024, (int) config('media.part_kilobytes', 5120));

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0', 'max:'.($maxParts - 1)],
            'total' => ['required', 'integer', 'min:1', 'max:'.$maxParts],
            'size' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:180'],
            'mime' => ['nullable', 'string', 'max:120'],
            'file' => ['required', 'file', 'max:'.$partKilobytes],
        ], [
            'file.required' => 'اختار ملف الفيديو.',
            'file.max' => 'حجم جزء الرفع أكبر من المسموح.',
        ]);

        @set_time_limit(120);
        $this->media->writePart(
            $request->user(),
            $data['upload_id'],
            (int) $data['index'],
            (int) $data['total'],
            (int) $data['size'],
            $data['name'],
            (string) ($data['mime'] ?? ''),
            $request->file('file'),
        );

        return response()->json(['ok' => true]);
    }

    public function finishParts(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
        ]);
        @set_time_limit(0);
        $stored = $this->media->assemble($request->user(), $data['upload_id']);

        return response()->json(['media_id' => $stored->id]);
    }

    public function storeVideo(Request $request, Unit $unit)
    {
        if ($request->filled('media_id')) {
            $data = $request->validate([
                'media_id' => ['required', 'integer'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'locks_at' => ['nullable', 'date'],
                'published' => ['nullable', 'boolean'],
            ], [
                'title.required' => 'اكتب عنوان الفيديو.',
            ]);
            $stored = $this->ownedVideo($request, (int) $data['media_id']);
            $video = $this->library->addPreparedVideo($unit, $request->user(), $stored, [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'locks_at' => isset($data['locks_at']) ? Carbon::parse($data['locks_at']) : null,
                'published' => $request->boolean('published'),
            ], []);

            return response()->json(['id' => $video->id], 201);
        }

        if ($request->filled('ticket')) {
            $data = $request->validate([
                'ticket' => ['required', 'string', 'max:80'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'locks_at' => ['nullable', 'date'],
                'published' => ['nullable', 'boolean'],
            ], [
                'title.required' => 'اكتب عنوان الفيديو.',
            ]);
            $stored = $this->media->claim($request->user(), $data['ticket']);
            abort_unless($stored->purpose === 'video', 422);
            $video = $this->library->addPreparedVideo($unit, $request->user(), $stored, [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'locks_at' => isset($data['locks_at']) ? Carbon::parse($data['locks_at']) : null,
                'published' => $request->boolean('published'),
            ], []);

            return response()->json(['id' => $video->id], 201);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'locks_at' => ['nullable', 'date'],
            'published' => ['nullable', 'boolean'],
            'video' => ['required', 'file', 'max:'.(int) config('media.max_kilobytes')],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:'.(int) config('media.max_kilobytes')],
        ], [
            'title.required' => 'اكتب عنوان الفيديو.',
            'video.required' => 'اختار ملف الفيديو.',
            'video.max' => 'حجم الفيديو أكبر من المسموح.',
            'attachments.max' => 'عدد المرفقات أكبر من المسموح.',
        ]);

        $video = $this->library->addVideo(
            $unit,
            $request->user(),
            $request->file('video'),
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'locks_at' => $data['locks_at'] ?? null,
                'published' => $request->boolean('published'),
            ],
            $request->file('attachments', []) ?? [],
        );

        return response()->json(['id' => $video->id], 201);
    }

    public function updateVideo(Request $request, Video $video)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'locks_at' => ['nullable', 'date'],
            'published' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ], [
            'title.required' => 'اكتب عنوان الفيديو.',
        ]);
        if (array_key_exists('locks_at', $data)) {
            $data['locks_at'] = $data['locks_at'] ? Carbon::parse($data['locks_at']) : null;
        }
        $this->library->updateVideo($video, $data);

        return response()->json(['ok' => true]);
    }

    public function replaceVideo(Request $request, Video $video)
    {
        if ($request->filled('media_id')) {
            $data = $request->validate([
                'media_id' => ['required', 'integer'],
            ]);
            $stored = $this->ownedVideo($request, (int) $data['media_id']);
            $this->library->replaceFromMedia($video, $request->user(), $stored);

            return response()->json(['ok' => true]);
        }

        if ($request->filled('ticket')) {
            $data = $request->validate([
                'ticket' => ['required', 'string', 'max:80'],
            ]);
            $stored = $this->media->claim($request->user(), $data['ticket']);
            abort_unless($stored->purpose === 'video', 422);
            $this->library->replaceFromMedia($video, $request->user(), $stored);

            return response()->json(['ok' => true]);
        }

        $request->validate([
            'video' => ['required', 'file', 'max:'.(int) config('media.max_kilobytes')],
        ], [
            'video.required' => 'اختار ملف الفيديو.',
        ]);
        $this->library->replaceVideo($video, $request->user(), $request->file('video'));

        return response()->json(['ok' => true]);
    }

    public function storeAttachments(Request $request, Video $video)
    {
        $request->validate([
            'attachments' => ['required', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:'.(int) config('media.max_kilobytes')],
        ], [
            'attachments.required' => 'اختار مرفق.',
        ]);
        $this->library->addAttachments($video, $request->user(), $request->file('attachments', []) ?? []);

        return response()->json(['ok' => true]);
    }

    public function destroyFile(Request $request, Video $video, VideoFile $file)
    {
        abort_unless((int) $file->video_id === (int) $video->id, 404);
        $this->library->deleteFile($file, $request->user());

        return response()->json(['ok' => true]);
    }

    public function destroyVideo(Request $request, Video $video)
    {
        $this->library->deleteVideo($video, $request->user());

        return response()->json(['ok' => true]);
    }

    public function storeCover(Request $request, StudyMonth $month)
    {
        $this->attachCover($request, $month);

        return response()->json(['ok' => true]);
    }

    public function cover(Request $request, StudyMonth $month)
    {
        $month->load('cover');
        abort_unless(
            $month->cover && $month->cover->purpose === 'cover' && str_starts_with((string) $month->cover->mime, 'image/'),
            404,
        );

        return $this->covers->response($request, $month->cover, false, 720);
    }

    private function ownedVideo(Request $request, int $id): Media
    {
        $media = Media::query()->find($id);
        abort_unless(
            $media && (int) $media->uploaded_by === (int) $request->user()->id && $media->purpose === 'video',
            404,
        );
        abort_if(Video::query()->where('media_id', $media->id)->exists(), 422);

        return $media;
    }

    public function stream(Request $request, Video $video)
    {
        $video->loadMissing('media');
        abort_unless($video->media, 404);

        // المعاينة بتاخد رابط مؤقت من R2 بدل ما PHP يحمّل الفيديو كله (كان بيوقع بعد 30 ثانية)
        if ($request->boolean('url')) {
            return response()->json([
                'url' => $this->media->temporaryUrl($video->media, (int) config('media.stream_minutes')),
            ]);
        }

        return $this->stream->response($video->media, $request->header('Range'));
    }

    public function file(Request $request, Video $video, VideoFile $file)
    {
        abort_unless((int) $file->video_id === (int) $video->id, 404);
        $file->loadMissing('media');
        abort_unless($file->media, 404);

        return $this->stream->response($file->media, $request->header('Range'), true);
    }

    private function monthData(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'grade' => [$creating ? 'required' : 'sometimes', 'string', 'exists:grades,slug'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'published' => ['nullable', 'boolean'],
            'locks_at' => ['nullable', 'date'],
        ], [
            'grade.required' => 'اختار الصف.',
            'title.required' => 'اكتب اسم الشهر.',
        ]);

        if (isset($data['grade'])) {
            $data['grade_id'] = Grade::query()->where('slug', $data['grade'])->value('id');
            unset($data['grade']);
        }

        if (array_key_exists('locks_at', $data) && $data['locks_at']) {
            $data['locks_at'] = Carbon::parse($data['locks_at']);
        }

        return $data;
    }

    private function attachCover(Request $request, StudyMonth $month): void
    {
        $file = $request->file('cover');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return;
        }

        $previous = $month->cover;
        $stored = $this->media->store($file, 'cover', $request->user());
        $month->cover_media_id = $stored->id;
        $month->save();

        if ($previous) {
            $this->media->delete($previous, $request->user());
        }
    }

    private function unitData(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'published' => ['nullable', 'boolean'],
            'locks_at' => ['nullable', 'date'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'title.required' => 'اكتب اسم القسم.',
        ]);

        if (array_key_exists('locks_at', $data) && $data['locks_at']) {
            $data['locks_at'] = Carbon::parse($data['locks_at']);
        }

        return $data;
    }
}
