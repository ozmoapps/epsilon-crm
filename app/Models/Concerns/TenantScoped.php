<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait.
     */
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! isset($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->id();
                
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Bypass the tenant scope.
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
