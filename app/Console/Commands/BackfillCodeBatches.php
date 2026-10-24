<?php

namespace App\Console\Commands;

use App\Enums\CodeType;
use App\Models\AccessCode;
use App\Models\CodeBatch;
use App\Models\StudyMonth;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCodeBatches extends Command
{
    protected $signature = 'codes:backfill-batches';

    protected $description = 'Group legacy access codes without a batch into generation batches';

    public function handle(): int
    {
        $codes = AccessCode::query()
            ->whereNull('batch_id')
            ->orderBy('id')
            ->get();

        if ($codes->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $clusters = [];
        $current = null;

        foreach ($codes as $code) {
            $type = $code->type instanceof CodeType ? $code->type->value : (string) $code->type;
            $sameUser = $type !== CodeType::Parent->value
                || (int) ($current['user_id'] ?? 0) === (int) ($code->user_id ?? 0);
            $same = $current
                && $current['issued_by'] === (int) $code->issued_by
                && $current['type'] === $type
                && (int) ($current['study_month_id'] ?? 0) === (int) ($code->study_month_id ?? 0)
                && $sameUser
                && (int) ($current['duration_days'] ?? 0) === (int) ($code->duration_days ?? 0)
                && abs($code->created_at->diffInSeconds($current['last_at'])) <= 180;

            if (! $same) {
                if ($current) {
                    $clusters[] = $current;
                }
                $current = [
                    'issued_by' => (int) $code->issued_by,
                    'type' => $type,
                    'study_month_id' => $code->study_month_id,
                    'user_id' => $code->user_id,
                    'duration_days' => $code->duration_days,
                    'codes' => collect([$code]),
                    'last_at' => $code->created_at,
                    'first_at' => $code->created_at,
                ];
            } else {
                $current['codes']->push($code);
                $current['last_at'] = $code->created_at;
            }
        }

        if ($current) {
            $clusters[] = $current;
        }

        $created = 0;

        DB::transaction(function () use ($clusters, &$created) {
            foreach ($clusters as $cluster) {
                $first = $cluster['codes']->first();
                $validDays = null;
                if ($first->expires_at && $first->created_at) {
                    $validDays = max(1, (int) $first->created_at->diffInDays($first->expires_at));
                }

                $batch = CodeBatch::query()->create([
                    'issued_by' => $cluster['issued_by'],
                    'type' => $cluster['type'],
                    'study_month_id' => $cluster['study_month_id'],
                    'user_id' => $cluster['type'] === CodeType::Parent->value ? $cluster['user_id'] : null,
                    'label' => $this->labelFor($cluster),
                    'code_valid_days' => $validDays,
                    'duration_days' => $cluster['duration_days'],
                    'quantity' => $cluster['codes']->count(),
                    'created_at' => $cluster['first_at'],
                    'updated_at' => $cluster['last_at'],
                ]);

                AccessCode::query()
                    ->whereIn('id', $cluster['codes']->pluck('id'))
                    ->update(['batch_id' => $batch->id]);

                $created++;
            }
        });

        $this->info("Created {$created} batches from ".$codes->count().' legacy codes.');

        return self::SUCCESS;
    }

    private function labelFor(array $cluster): string
    {
        if ($cluster['type'] === CodeType::Parent->value && $cluster['user_id']) {
            return (string) (User::query()->whereKey($cluster['user_id'])->value('name') ?: 'ولي أمر');
        }

        if ($cluster['study_month_id']) {
            $month = StudyMonth::query()->with('grade:id,title')->find($cluster['study_month_id']);
            if ($month) {
                return $month->grade?->title
                    ? $month->title.' · '.$month->grade->title
                    : (string) $month->title;
            }
        }

        return 'دفعة قديمة';
    }
}
