<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'grade_id',
        'study_month_id',
        'title',
        'instructions',
        'published',
        'permanent',
        'duration_minutes',
        'pass_percent',
        'closes_at',
        'opens_at',
        'attempts_allowed',
        'total_marks',
        'passing_marks',
        'shuffle_questions',
        'shuffle_choices',
        'explanation_when',
        'show_results',
        'show_answers',
        'show_explanations',
        'allow_review',
        'prevent_tab_switch',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'permanent' => 'boolean',
            'closes_at' => 'datetime',
            'opens_at' => 'datetime',
            'total_marks' => 'float',
            'passing_marks' => 'float',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'show_results' => 'boolean',
            'show_answers' => 'boolean',
            'show_explanations' => 'boolean',
            'allow_review' => 'boolean',
            'prevent_tab_switch' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(StudyMonth::class, 'study_month_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot(['points', 'sort', 'snapshot'])
            ->withTimestamps()
            ->orderByPivot('sort');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
