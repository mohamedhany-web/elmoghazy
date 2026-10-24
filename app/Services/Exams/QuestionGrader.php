<?php

namespace App\Services\Exams;

class QuestionGrader
{
    /**
     * @param  array<string, mixed>  $spec
     * @param  array{option_ids?: list<int|string>, response?: ?string}  $answer
     * @return array{awarded: float, correct: bool, pending: bool}
     */
    public function score(array $spec, array $answer, float $points): array
    {
        $type = (string) ($spec['type'] ?? 'single');
        $picked = collect($answer['option_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $options = collect($spec['options'] ?? []);
        $owned = $options->pluck('id')->map(fn ($id) => (int) $id);
        $picked = $picked->intersect($owned)->sort()->values();
        $correctIds = $options->filter(fn ($option) => ! empty($option['is_correct']))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $response = trim((string) ($answer['response'] ?? ''));
        $settings = is_array($spec['settings'] ?? null) ? $spec['settings'] : [];

        if (in_array($type, ['essay', 'short', 'matching', 'ordering'], true)) {
            return ['awarded' => 0.0, 'correct' => false, 'pending' => true];
        }

        $text = $answer['answer'] ?? null;
        if ($picked->isEmpty() && $text !== null && $text !== '' && $text !== []) {
            return $this->scoreText($type, $spec, $text, $response, $points, $settings);
        }

        $full = $this->isFull($type, $picked, $correctIds, $response, $settings);
        if ($type === 'multiple' && ($settings['scoring'] ?? 'all') !== 'all') {
            $awarded = $this->partial($picked, $correctIds, $points, (string) $settings['scoring']);

            return [
                'awarded' => $awarded,
                'correct' => $points > 0 && abs($awarded - $points) < 0.001,
                'pending' => false,
            ];
        }

        if ($full) {
            return ['awarded' => $points, 'correct' => true, 'pending' => false];
        }

        $penalty = isset($spec['negative_mark']) && $spec['negative_mark'] !== null && $spec['negative_mark'] !== ''
            ? (float) $spec['negative_mark']
            : 0.0;
        $miss = $picked->isNotEmpty() || $response !== '';

        return [
            'awarded' => $miss ? -1 * abs($penalty) : 0.0,
            'correct' => false,
            'pending' => false,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $picked
     * @param  \Illuminate\Support\Collection<int, int>  $correctIds
     * @param  array<string, mixed>  $settings
     */
    private function isFull($type, $picked, $correctIds, string $response, array $settings): bool
    {
        if (in_array($type, ['single', 'multiple', 'true_false', 'multiple_choice'], true)) {
            return $picked->all() === $correctIds->all() && $correctIds->isNotEmpty();
        }

        if ($type === 'fill_blank') {
            $accepted = collect($settings['answers'] ?? [])->map(fn ($item) => $this->fold((string) $item, false))->filter();
            $given = $this->fold($response, false);

            return $given !== '' && $accepted->contains($given);
        }

        if ($type === 'numerical') {
            if ($response === '' || ! is_numeric($response) || ! is_numeric($settings['answer'] ?? null)) {
                return false;
            }
            $tolerance = is_numeric($settings['tolerance'] ?? null) ? (float) $settings['tolerance'] : 0.0;

            return abs((float) $response - (float) $settings['answer']) <= abs($tolerance);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array<string, mixed>  $settings
     * @return array{awarded: float, correct: bool, pending: bool}
     */
    private function scoreText(string $type, array $spec, mixed $text, string $response, float $points, array $settings): array
    {
        $options = collect($spec['options'] ?? []);
        $correctBodies = $options->filter(fn ($option) => ! empty($option['is_correct']))->map(fn ($option) => trim((string) ($option['body'] ?? '')))->filter()->values();

        if (in_array($type, ['single', 'true_false', 'multiple_choice'], true)) {
            $given = trim(is_array($text) ? (string) ($text[0] ?? '') : (string) $text);
            $ok = $correctBodies->contains(function (string $body) use ($type, $given) {
                if ($type === 'true_false') {
                    return $this->truth($given) === $this->truth($body);
                }

                return $given === $body;
            });

            return $ok
                ? ['awarded' => $points, 'correct' => true, 'pending' => false]
                : ['awarded' => 0.0, 'correct' => false, 'pending' => false];
        }

        if ($type === 'fill_blank') {
            $given = $this->fold(trim((string) ($response !== '' ? $response : (is_array($text) ? ($text[0] ?? '') : $text))), false);
            $accepted = collect($settings['answers'] ?? [])->map(fn ($item) => $this->fold((string) $item, false))->filter();
            $ok = $given !== '' && $accepted->contains($given);

            return $ok
                ? ['awarded' => $points, 'correct' => true, 'pending' => false]
                : ['awarded' => 0.0, 'correct' => false, 'pending' => false];
        }

        if ($type === 'numerical') {
            $given = trim((string) ($response !== '' ? $response : (is_array($text) ? '' : $text)));
            $full = $this->isFull($type, collect(), collect(), $given, $settings);

            return $full
                ? ['awarded' => $points, 'correct' => true, 'pending' => false]
                : ['awarded' => 0.0, 'correct' => false, 'pending' => false];
        }

        return ['awarded' => 0.0, 'correct' => false, 'pending' => false];
    }

    private function truth(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'صح', 'ص', 'true', '1', 'yes' => 'صح',
            'خطأ', 'خطا', 'غلط', 'false', '0', 'no' => 'خطأ',
            default => $value,
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $picked
     * @param  \Illuminate\Support\Collection<int, int>  $correctIds
     */
    private function partial($picked, $correctIds, float $points, string $scoring): float
    {
        $right = $picked->intersect($correctIds)->count();
        $wrong = $picked->diff($correctIds)->count();
        $unit = $correctIds->count() > 0 ? $points / $correctIds->count() : 0;
        $raw = $scoring === 'deduct' ? ($right - $wrong) * $unit : $right * $unit;

        return round(max(0, $raw), 2);
    }

    private function fold(string $value, bool $case): string
    {
        $value = trim($value);

        return $case ? $value : mb_strtolower($value);
    }
}
