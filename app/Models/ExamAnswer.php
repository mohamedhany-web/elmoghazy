<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'selected',
        'correct',
        'awarded',
        'max_points',
        'response',
        'pending',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'selected' => 'array',
            'correct' => 'boolean',
            'awarded' => 'float',
            'max_points' => 'float',
            'pending' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
