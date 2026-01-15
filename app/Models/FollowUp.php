<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'next_at',
        'note',
        'created_by',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'next_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('completed_at');
    }

    public function getIsCompletedAttribute(): bool
    {
        return ! is_null($this->completed_at);
    }

    public function isOverdue(): bool
    {
        return ! $this->is_completed && $this->next_at && $this->next_at->isPast();
    }

    public function getTitleAttribute(): string
    {
        // Fallback to note or subject name or absolute fallback
        return $this->note ?? $this->subject?->name ?? 'Takip #' . $this->id;
    }
}
