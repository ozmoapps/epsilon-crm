<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use App\Services\TenantContext;

trait TenantGuard
{
    /**
     * Abort with 404 if the model does not belong to the current tenant.
     */
    protected function checkTenant(Model $model): void
    {
        if ($model->tenant_id != app(TenantContext::class)->id()) {
            abort(404);
        }
    }

    /**
     * Scope a query to the current tenant.
     */
    protected function tenantScope($query)
    {
        return $query->where('tenant_id', app(TenantContext::class)->id());
    }
}
