<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class StudyMonth extends Model
{
    protected $fillable = [
        'grade_id',
        'cover_media_id',
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

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort');
    }

    public function videos(): HasManyThrough
    {
        return $this->hasManyThrough(Video::class, Unit::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
