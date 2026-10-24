<?php

namespace App\Services\Storage;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateStream
{
    /** أقصى قطعة لكل طلب فيديو — يمنع حجز php artisan serve بالملف كاملًا */
    private const CHUNK = 262144;

    public function response(Media $media, ?string $range, bool $download = false): StreamedResponse
    {
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->key), 404);

        $size = (int) $disk->size($media->key);
        $start = 0;
        $end = max(0, $size - 1);
        $status = 200;

        if ($download) {
            if ($range !== null && $range !== '') {
                if (! preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $match) || $size === 0) {
                    return $this->unsatisfiable($size);
                }
                $start = $match[1] === '' ? 0 : (int) $match[1];
                $end = $match[2] === '' ? $size - 1 : (int) $match[2];
                if ($start > $end || $start >= $size) {
                    return $this->unsatisfiable($size);
                }
                $end = min($end, $size - 1);
                $status = 206;
            }
        } else {
            // الفيديو دائمًا 206 بقطع قصيرة حتى لو المتصفح طلب الملف كله
            if ($range !== null && $range !== '' && preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $match) && $size > 0) {
                $start = $match[1] === '' ? 0 : (int) $match[1];
                $end = $match[2] === '' ? $size - 1 : (int) $match[2];
                if ($start > $end || $start >= $size) {
                    return $this->unsatisfiable($size);
                }
                $end = min($end, $size - 1);
            } else {
                $start = 0;
                $end = max(0, $size - 1);
            }

            $cap = $start + self::CHUNK - 1;
            if ($end > $cap) {
                $end = min($cap, $size - 1);
            }
            $status = 206;
        }

        $length = $end - $start + 1;
        $headers = [
            'Content-Type' => $media->mime,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'same-origin',
            'Content-Disposition' => $download
                ? "attachment; filename*=UTF-8''".rawurlencode($media->original_name)
                : 'inline; filename="lecture.mp4"',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($disk, $media, $start, $end) {
            ignore_user_abort(false);
            $stream = $this->open($disk, $media, $start, $end);
            if (! is_resource($stream)) {
                return;
            }

            $left = $end - $start + 1;
            while ($left > 0 && ! feof($stream) && ! connection_aborted()) {
                $chunk = fread($stream, (int) min(65536, $left));
                if ($chunk === false || $chunk === '') {
                    break;
                }
                echo $chunk;
                $left -= strlen($chunk);
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            fclose($stream);
        }, $status, $headers);
    }

    private function open($disk, Media $media, int $start, int $end)
    {
        if (method_exists($disk, 'getClient')) {
            $bucket = config('filesystems.disks.'.$media->disk.'.bucket');
            $result = $disk->getClient()->getObject([
                'Bucket' => $bucket,
                'Key' => $media->key,
                'Range' => "bytes={$start}-{$end}",
            ]);

            return $result['Body']->detach();
        }

        $stream = $disk->readStream($media->key);
        if ($start > 0 && is_resource($stream)) {
            fseek($stream, $start);
        }

        return $stream;
    }

    private function unsatisfiable(int $size): StreamedResponse
    {
        return response()->stream(function () {}, 416, [
            'Content-Range' => "bytes */{$size}",
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
