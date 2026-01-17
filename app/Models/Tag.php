<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    use \App\Support\TenantGuard;

    protected $fillable = ['name', 'color', 'tenant_id'];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
