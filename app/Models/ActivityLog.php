<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'meta',
        'created_at',
        'tenant_id',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function getDescriptionAttribute(): string
    {
        $actorName = $this->actor ? $this->actor->name : 'Sistem';
        $action = match ($this->action) {
            'created' => 'oluşturdu',
            'updated' => 'güncelledi',
            'deleted' => 'sildi',
            'login' => 'giriş yaptı',
            default => $this->action,
        };
        $subjectName = $this->subject_type ? class_basename($this->subject_type) : 'Öğe';

        return "{$actorName} {$subjectName} {$action}";
    }
}
