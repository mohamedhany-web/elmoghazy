<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudyMonth;
use App\Models\Unit;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoFile;
use App\Services\Access\StreamTicket;
use App\Services\Access\SubscriptionAccess;
use App\Services\Access\VideoGate;
use App\Services\Storage\MediaStore;
use App\Services\Storage\PrivateStream;
use Illuminate\Http\Request;
use Throwable;

class LibraryController extends Controller
{
    public function __construct(
        private readonly VideoGate $gate,
        private readonly SubscriptionAccess $access,
        private readonly PrivateStream $stream,
        private readonly StreamTicket $tickets,
        private readonly MediaStore $media,
    ) {}

    public function index(Request $request)
    {
        $student = $request->user();

        if (! $student->isActive() || ! $student->grade_id) {
            return response()->json([]);
        }

        $months = StudyMonth::query()
            ->where('grade_id', $student->grade_id)
            ->where('published', true)
            ->with(['units' => fn ($query) => $query->where('published', true)->orderBy('sort')->with(['videos' => fn ($videos) => $videos->where('published', true)->orderBy('sort')->with('files.media')])])
            ->orderBy('sort')
            ->get()
            ->filter(fn (StudyMonth $month) => $this->access->seesMonth($student, $month->id))
            ->values();

        return response()->json($months->map(function (StudyMonth $month) use ($student) {
            return [
                'id' => $month->id,
                'title' => $month->title,
                'description' => $month->description,
                'has_cover' => (bool) $month->cover_media_id,
                'locks_at' => $month->locks_at,
                'units' => $month->units->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'title' => $unit->title,
                    'description' => $unit->description,
                    'locks_at' => $unit->locks_at,
                    'videos' => $unit->videos->map(function (Video $video) use ($student) {
                        $decision = $this->gate->decide($student, $video);

                        return [
                            'id' => $video->id,
                            'title' => $video->title,
                            'description' => $video->description,
                            'locked' => $decision['locked'] || ! $decision['allowed'],
                            'lock_message' => $decision['allowed'] ? null : $decision['message'],
                            'locks_at' => $this->gate->locksAt($video),
                            'files' => $video->files->map(fn (VideoFile $file) => [
                                'id' => $file->id,
                                'name' => $file->media?->original_name,
                            ]),
                        ];
                    }),
                ]),
            ];
        }));
    }

    public function ticket(Request $request, Video $video)
    {
        $this->gate->assertWatch($request->user(), $video);
        $video->loadMissing('media');
        abort_unless($video->media, 404);

        return response()->json([
            'token' => $this->tickets->issue((int) $request->user()->id, (int) $video->id),
        ]);
    }

    public function stream(Request $request, Video $video)
    {
        $this->assertPlatformClient($request, true);
        $token = (string) $request->query('t', '');
        $payload = $this->tickets->read($token);
        abort_unless($payload && (int) $payload['video_id'] === (int) $video->id, 403);

        $viewer = $request->user('sanctum');
        if ($viewer) {
            abort_unless((int) $viewer->id === (int) $payload['user_id'], 403);
            $student = $viewer;
        } else {
            $student = User::query()->find($payload['user_id']);
            abort_unless($student, 403);
        }

        $this->gate->assertWatch($student, $video);
        $video->loadMissing('media');
        abort_unless($video->media, 404);

        // لا نمرّر بايتات الفيديو عبر PHP أبدًا — التحويل المؤقت يمنع تعليق السيرفر
        try {
            return redirect()->away($this->media->temporaryUrl($video->media, (int) config('media.stream_minutes')));
        } catch (Throwable $exception) {
            report($exception);
            abort(503, 'تعذر تشغيل الفيديو.');
        }
    }

    public function file(Request $request, Video $video, VideoFile $file)
    {
        $this->assertPlatformClient($request);
        abort_unless((int) $file->video_id === (int) $video->id, 404);
        $this->gate->assertWatch($request->user(), $video);
        $file->loadMissing('media');
        abort_unless($file->media, 404);

        return $this->stream->response($file->media, $request->header('Range'), true);
    }

    private function assertPlatformClient(Request $request, bool $allowBare = false): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $allowed = parse_url(rtrim((string) config('app.url'), '/'), PHP_URL_HOST);
        $hosts = array_filter([
            is_string($allowed) ? $allowed : null,
            $request->getHost(),
        ]);

        $origin = (string) $request->headers->get('Origin', '');
        $referer = (string) $request->headers->get('Referer', '');
        // مشغّل الفيديو في آيفون (AppleCoreMedia) مبيبعتش Referer — التذكرة نفسها هي الحماية هنا
        if ($allowBare && $origin === '' && $referer === '') {
            return;
        }

        $originHost = $origin !== '' ? parse_url($origin, PHP_URL_HOST) : null;
        $refererHost = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : null;

        $ok = ($originHost && in_array($originHost, $hosts, true))
            || ($refererHost && in_array($refererHost, $hosts, true));

        abort_unless($ok, 403);
    }
}
