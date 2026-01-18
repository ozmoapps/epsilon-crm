<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    use \App\Models\Concerns\TenantScoped;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    protected $fillable = [
        'sales_order_id',
        'tenant_id',
        'customer_id',
        'invoice_no',
        'status',
        'payment_status',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'tax_total',
        'total',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
        'payment_status' => 'unpaid',
        'currency' => 'EUR',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getAllocatedTotalAttribute()
    {
        return (float) $this->paymentAllocations->sum('amount');
    }


    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft' => 'Taslak',
            'issued' => 'Kesildi',
            'cancelled' => 'İptal',
            default => $this->status,
        };
    }

    public function getPaymentStatusLabelAttribute()
    {
        return match ($this->payment_status) {
            'unpaid' => 'Ödenmedi',
            'partial' => 'Kısmi Ödeme',
            'paid' => 'Ödendi',
            default => $this->payment_status,
        };
    }
}
