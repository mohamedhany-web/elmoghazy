<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'study_month_id',
        'title',
        'description',
        'sort',
        'published',
        'locks_at',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'locks_at' => 'datetime',
        ];
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(StudyMonth::class, 'study_month_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('sort');
    }
}
