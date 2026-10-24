<?php

namespace App\Models;

use App\Enums\ActivationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activation extends Model
{
    protected $hidden = [
        'device_hash',
    ];

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'study_month_id',
        'access_code_id',
        'source',
        'device_hash',
        'starts_at',
        'ends_at',
        'status',
        'locked_at',
        'lock_source',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'locked_at' => 'datetime',
            'status' => ActivationStatus::class,
        ];
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

    public function code(): BelongsTo
    {
        return $this->belongsTo(AccessCode::class, 'access_code_id');
    }
}
