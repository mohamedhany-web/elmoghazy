<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'bank_lesson_id',
        'bank_topic_id',
        'prompt',
        'type',
        'difficulty',
        'marks',
        'negative_mark',
        'explanation',
        'hint',
        'status',
        'tags',
        'settings',
        'favorite',
        'sort',
        'time_limit',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'float',
            'negative_mark' => 'float',
            'tags' => 'array',
            'settings' => 'array',
            'favorite' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BankLesson::class, 'bank_lesson_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(BankTopic::class, 'bank_topic_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }
}
