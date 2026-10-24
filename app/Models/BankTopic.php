<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTopic extends Model
{
    protected $fillable = ['bank_lesson_id', 'title', 'sort'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BankLesson::class, 'bank_lesson_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
