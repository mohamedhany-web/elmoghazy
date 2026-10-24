<?php

namespace App\Services\Reports;

use App\Support\EgyptianPhone;
use Illuminate\Support\Carbon;

class ReportSheet
{
    public static function date(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->locale('ar')->translatedFormat('j M Y');
    }

    /**
     * @param  list<array{label: string, value: string}>  $facts
     * @param  list<array{label: string, value: string}>  $stats
     * @param  list<array{title: string, columns: list<string>, rows: list<list<string>>, empty: string}>  $sections
     * @return array<string, mixed>
     */
    public static function make(string $title, string $subject, string $kicker, array $facts, array $stats, array $sections, ?string $phone = null): array
    {
        return [
            'title' => $title,
            'subject' => $subject,
            'kicker' => $kicker,
            'issued_at' => self::date(now()),
            'facts' => $facts,
            'stats' => $stats,
            'sections' => $sections,
            'whatsapp' => self::whatsapp($phone),
        ];
    }

    private static function whatsapp(?string $phone): ?string
    {
        $digits = EgyptianPhone::normalize($phone);

        return preg_match('/^01[0125]\d{8}$/', $digits) ? '20'.substr($digits, 1) : null;
    }
}
