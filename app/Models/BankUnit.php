<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankUnit extends Model
{
    protected $fillable = ['question_bank_id', 'title', 'sort'];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(BankLesson::class)->orderBy('sort');
    }
}
