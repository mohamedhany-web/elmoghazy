<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'disk',
        'key',
        'purpose',
        'mime',
        'size',
        'original_name',
        'uploaded_by',
    ];

    protected $hidden = [
        'key',
        'disk',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
