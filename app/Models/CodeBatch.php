<?php

namespace App\Models;

use App\Enums\CodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeBatch extends Model
{
    protected $fillable = [
        'issued_by',
        'type',
        'study_month_id',
        'user_id',
        'label',
        'code_valid_days',
        'duration_days',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'type' => CodeType::class,
        ];
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(StudyMonth::class, 'study_month_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(AccessCode::class, 'batch_id');
    }
}
