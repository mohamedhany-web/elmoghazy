<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\StudyMonth;
use App\Models\Unit;
use App\Models\Video;
use App\Services\Catalog\PurchaseRequests;
use App\Services\Storage\CoverPreview;
use Illuminate\Http\Request;
use Laravel\Sanctum\Guard;

class MonthCatalogController extends Controller
{
    public function __construct(private readonly PurchaseRequests $requests) {}

    public function index(Request $request)
    {
        $slug = $request->query('grade');

        $rows = StudyMonth::query()
            ->where('published', true)
            ->with('grade:id,title,slug,accent')
            ->withCount([
                'units' => fn ($query) => $query->where('published', true),
                'videos' => fn ($query) => $query->where('videos.published', true),
                'purchaseRequests as sales_count',
            ])
            ->when(is_string($slug) && $slug !== '', function ($query) use ($slug) {
                $query->whereHas('grade', fn ($grade) => $grade->where('slug', $slug));
            })
            ->orderByDesc('sales_count')
            ->orderBy('sort')
            ->get();

        return response()->json($rows->map(fn (StudyMonth $month) => $this->card($month)));
    }

    public function cover(Request $request, StudyMonth $month, CoverPreview $preview)
    {
        abort_unless($month->published, 404);
        $month->load('cover');
        abort_unless(
            $month->cover && $month->cover->purpose === 'cover' && str_starts_with((string) $month->cover->mime, 'image/'),
            404,
        );

        return $preview->response($request, $month->cover, true, 960);
    }

    public function show(StudyMonth $month)
    {
        abort_unless($month->published, 404);

        $month->load([
            'grade:id,title,slug,accent',
            'units' => fn ($query) => $query->where('published', true)->orderBy('sort')->with([
                'videos' => fn ($videos) => $videos->where('published', true)->orderBy('sort'),
            ]),
        ]);
        $month->loadCount('purchaseRequests as sales_count');

        return response()->json([
            ...$this->card($month),
            'unit_count' => $month->units->count(),
            'video_count' => $month->units->sum(fn (Unit $unit) => $unit->videos->count()),
            'description' => $month->description,
            'locks_at' => $month->locks_at,
            'units' => $month->units->map(fn (Unit $unit) => [
                'title' => $unit->title,
                'description' => $unit->description,
                'videos' => $unit->videos->map(fn (Video $video) => [
                    'title' => $video->title,
                ]),
            ]),
        ]);
    }

    public function storeRequest(Request $request, StudyMonth $month)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'اكتب الاسم.',
            'phone.required' => 'اكتب رقم الموبايل.',
        ]);

        $row = $this->requests->place(
            $month,
            $data['name'],
            $data['phone'],
            $data['note'] ?? null,
            $request->user() ?? app(Guard::class)($request),
        );

        return response()->json(['id' => $row->id], 201);
    }

    private function card(StudyMonth $month): array
    {
        return [
            'id' => $month->id,
            'title' => $month->title,
            'grade' => $month->grade?->title,
            'grade_slug' => $month->grade?->slug,
            'accent' => $month->grade?->accent ?? 'cyan',
            'unit_count' => (int) ($month->units_count ?? $month->units->count()),
            'video_count' => (int) ($month->videos_count ?? 0),
            'sales_count' => (int) ($month->sales_count ?? 0),
            'has_cover' => (bool) $month->cover_media_id,
        ];
    }
}
