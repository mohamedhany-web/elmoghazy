<?php

return [

    /*
    |--------------------------------------------------------------------------
    | قرص التخزين
    |--------------------------------------------------------------------------
    |
    | كل الصور والفيديو والمرفقات تمر من MediaStore إلى هذا القرص.
    | القيمة r2 هي دلو Cloudflare الخاص. لا تُبنى روابط عامة ثابتة.
    |
    */

    'disk' => env('MEDIA_DISK', 'r2'),

    'temporary_minutes' => (int) env('R2_TEMPORARY_URL_MINUTES', 10),

    // رابط تشغيل الفيديو لازم يعيش طول مدة الحصة: المتصفح بيكمّل التحميل والتقديم على نفس الرابط
    'stream_minutes' => (int) env('MEDIA_STREAM_MINUTES', 240),

    // 2 جيجا بايت = 2097152 كيلوبايت
    'max_kilobytes' => (int) env('MEDIA_MAX_KILOBYTES', 2097152),

    // حجم جزء الرفع عبر السيرفر (كيلوبايت) — لازم يبقى تحت حد PHP upload
    'part_kilobytes' => (int) env('MEDIA_PART_KILOBYTES', 5120),

    'max_parts' => (int) env('MEDIA_MAX_PARTS', 512),

    'purposes' => [
        'lesson',
        'cover',
        'attachment',
        'video',
    ],

    'mimes' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/wav' => 'wav',
        'application/pdf' => 'pdf',
    ],

];
