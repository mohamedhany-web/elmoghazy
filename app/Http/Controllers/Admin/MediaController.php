<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Storage\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    public function __construct(private readonly MediaStore $store) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('media.max_kilobytes')],
            'purpose' => ['required', 'string', Rule::in(config('media.purposes'))],
        ], [
            'file.required' => 'اختار الملف.',
            'file.max' => 'حجم الملف أكبر من المسموح.',
            'purpose.required' => 'حدد نوع التخزين.',
            'purpose.in' => 'نوع التخزين غير معروف.',
        ]);

        $stored = $this->store->store($request->file('file'), $data['purpose'], $request->user());

        return response()->json($this->payload($stored), 201);
    }

    public function destroy(Request $request, Media $media)
    {
        $this->store->delete($media, $request->user());

        return response()->json(['ok' => true]);
    }

    private function payload(Media $media): array
    {
        return [
            'id' => $media->id,
            'purpose' => $media->purpose,
            'mime' => $media->mime,
            'size' => $media->size,
            'original_name' => $media->original_name,
        ];
    }
}
