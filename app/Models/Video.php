<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    protected $fillable = [
        'unit_id',
        'media_id',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(VideoFile::class);
    }
}
