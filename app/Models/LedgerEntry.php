<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'vessel_id',
        'type',
        'source_type',
        'source_id',
        'direction',
        'amount',
        'currency',
        'occurred_at',
        'description',
        'created_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                // Try to infer from Customer
                if ($model->customer_id) {
                     $model->tenant_id = \App\Models\Customer::where('id', $model->customer_id)->value('tenant_id');
                }
                
                // Fallback to Context
                if (!$model->tenant_id) {
                    $model->tenant_id = app(\App\Services\TenantContext::class)->id();
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    protected $casts = [
        'amount' => 'decimal:4',
        'occurred_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
