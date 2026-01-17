<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use \App\Support\TenantGuard;

    protected $fillable = ['name', 'tenant_id'];

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
        return $this->hasMany(Product::class);
    }
}
