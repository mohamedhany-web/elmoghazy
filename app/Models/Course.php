<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'grade_id',
        'slug',
        'title',
        'description',
        'lessons',
        'tag',
        'mode',
        'duration',
        'price',
        'sort',
        'published',
        'unlock_in_order',
        'access_unit',
        'access_amount',
        'access_starts_at',
        'access_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'unlock_in_order' => 'boolean',
            'access_starts_at' => 'datetime',
            'access_ends_at' => 'datetime',
        ];
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort');
    }
}
