<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id',
        'user_id',
        'status',
        'score',
        'total',
        'percentage',
        'started_at',
        'submitted_at',
        'time_taken',
        'answer_sheet',
        'presentation',
        'activity',
        'tab_switches',
        'auto_submitted',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'total' => 'float',
            'percentage' => 'float',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'answer_sheet' => 'array',
            'presentation' => 'array',
            'activity' => 'array',
            'auto_submitted' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }
}
