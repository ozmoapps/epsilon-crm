<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'tax_no',
        'footer_text',
        'tenant_id',
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    public static function current(): ?self
    {
        // Strict Scoping: Only return profile if it matches current tenant.
        // Falls back to null if no tenant context or no match. NO GLOBAL FALLBACK.
        $tenantId = app(\App\Services\TenantContext::class)->id();
        
        if (!$tenantId) {
             return null;
        }

        return static::where('tenant_id', $tenantId)->first();
    }
}
