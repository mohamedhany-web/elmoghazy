<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Grade extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'lessons',
        'accent',
        'sort',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class)->orderBy('sort');
    }

    public function studyMonths(): HasMany
    {
        return $this->hasMany(StudyMonth::class);
    }

    public function questionBank(): HasOne
    {
        return $this->hasOne(QuestionBank::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
