<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'tenant_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
    ];

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (! $customer->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $customer->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
}
