<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankLesson extends Model
{
    protected $fillable = ['bank_unit_id', 'title', 'sort'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(BankUnit::class, 'bank_unit_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(BankTopic::class)->orderBy('sort');
    }
}
