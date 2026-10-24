<?php

namespace App\Services\Storage;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CoverPreview
{
    public function response(Request $request, Media $media, bool $public, int $fallbackWidth): Response
    {
        $width = $this->width($request->query('w'), $fallbackWidth);
        $stamp = $media->updated_at?->getTimestamp() ?? $media->id;
        $etag = '"cover-'.$media->id.'-'.$stamp.'-'.$width.'"';
        $cache = $public ? 'public, max-age=604800' : 'private, max-age=86400';

        if (trim((string) $request->headers->get('If-None-Match')) === $etag) {
            return response('', 304, $this->headers($etag, $cache, 'image/jpeg'));
        }

        [$bytes, $mime] = $this->bytes($media, $width, $stamp);

        return response($bytes, 200, $this->headers($etag, $cache, $mime));
    }

    private function width(mixed $value, int $fallback): int
    {
        $width = is_numeric($value) ? (int) $value : $fallback;

        return max(320, min(1600, $width));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function bytes(Media $media, int $width, int $stamp): array
    {
        $dir = storage_path('framework/cache/covers');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $cached = $dir.'/'.$media->id.'-'.$stamp.'-'.$width;
        $mimeFile = $cached.'.mime';
        if (is_file($cached) && is_file($mimeFile)) {
            return [(string) file_get_contents($cached), trim((string) file_get_contents($mimeFile))];
        }

        $original = Storage::disk($media->disk)->get($media->key);
        [$bytes, $mime] = $this->shrink($original, (string) $media->mime, $width);
        file_put_contents($cached, $bytes);
        file_put_contents($mimeFile, $mime);

        return [$bytes, $mime];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function shrink(string $original, string $mime, int $width): array
    {
        $info = @getimagesizefromstring($original);
        if ($info === false || $info[0] <= $width || ! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return [$original, $mime];
        }

        $source = @imagecreatefromstring($original);
        if ($source === false) {
            return [$original, $mime];
        }

        $targetHeight = (int) max(1, round($info[1] * ($width / $info[0])));
        $target = imagecreatetruecolor($width, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $targetHeight, $info[0], $info[1]);
        ob_start();
        imagejpeg($target, null, 72);
        $bytes = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return [$bytes === '' ? $original : $bytes, $bytes === '' ? $mime : 'image/jpeg'];
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $etag, string $cache, string $mime): array
    {
        return [
            'Content-Type' => $mime,
            'Cache-Control' => $cache,
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
