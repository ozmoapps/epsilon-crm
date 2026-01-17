<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedView extends Model
{
    use \App\Support\TenantGuard;

    protected $fillable = ['scope', 'name', 'query', 'user_id', 'is_shared', 'tenant_id'];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    protected $casts = [
        'query' => 'array',
        'is_shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAllow($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('is_shared', true);
        });
    }
}
