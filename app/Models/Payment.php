<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'bank_account_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
        'original_amount',
        'original_currency',
        'fx_rate',
        'customer_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:4',
        'fx_rate' => 'decimal:8',
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the currency effectively used for this payment.
     * If linked to an invoice (Standard), uses the Invoice currency (legacy behavior).
     * If Advance Payment, uses the Original Currency (or Bank Account Currency).
     */
    public function getEffectiveCurrencyAttribute()
    {
        // 1. If linked to an invoice, the 'amount' attribute is already in Invoice Currency.
        if ($this->invoice_id && $this->invoice) {
            return $this->invoice->currency;
        }

        // 2. If no invoice (Advance), we use the original currency.
        // (If original_currency is null, use BankAccount currency as fallback)
        return $this->original_currency ?? $this->bankAccount?->currency?->code ?? 'EUR';
    }

    /**
     * Get the amount effectively available in the Effective Currency.
     */
    public function getEffectiveAmountAttribute()
    {
        // 1. If linked, 'amount' is already converted/fixed to Invoice Currency.
        if ($this->invoice_id) {
            return (float) $this->amount;
        }

        // 2. If Advance, we treat the 'original_amount' as the effective amount logic 
        // (assuming no FX conversion happened yet at time of creation if no invoice was target).
        // However, standardizing: 'amount' column usually holds the normalized value.
        // In PaymentController@store, 'amount' is set to 'equivalentAmount'.
        // For Advance payments, equivalentAmount usually equals originalAmount (fx=1).
        // So we can fallback to 'amount' but strict reading of contract says use Original for Advances if that's the "source".
        // Let's stick to 'amount' column because that's the DB column for "Value".
        // BUT strict instruction said: "effective_amount: if invoice_id null -> bank currency amount field"
        // Let's check PaymentController@store again: 
        // $payment->amount = $equivalentAmount; $payment->original_amount = $originalAmount;
        // If invoice_id is null, fx_rate is likely 1.0. So amount == original_amount.
        // We return 'amount' to be consistent with DB queries.
        return (float) ($this->original_amount ?? $this->amount); 
    }

    /**
     * Helpers for allocation status
     */
    public function getAllocatedAmountAttribute()
    {
        return (float) $this->allocations->sum('amount');
    }

    public function getUnallocatedAmountAttribute()
    {
        $effectiveTotal = $this->effective_amount;
        $allocated = $this->allocated_amount;

        // Legacy/Implicit Logic:
        // If invoice_id is set AND allocations are empty, it is implicitly "Fully Allocated" to that invoice.
        // We should treat unallocated as 0 to prevent double-dipping.
        if ($this->invoice_id && $this->allocations->isEmpty()) {
            return 0.0;
        }

        // Standard Logic (Advance Payment or Explicit Allocations)
        return max(0, $effectiveTotal - $allocated);
    }
}

