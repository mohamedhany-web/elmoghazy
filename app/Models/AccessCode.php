<?php

namespace App\Models;

use App\Enums\CodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCode extends Model
{
    protected $hidden = [
        'code_hash',
        'plain_code',
    ];

    protected $fillable = [
        'issued_by',
        'batch_id',
        'user_id',
        'course_id',
        'lesson_id',
        'study_month_id',
        'type',
        'code_hash',
        'hint',
        'plain_code',
        'duration_days',
        'max_uses',
        'uses',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CodeType::class,
            'plain_code' => 'encrypted',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CodeBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(StudyMonth::class, 'study_month_id');
    }
}
