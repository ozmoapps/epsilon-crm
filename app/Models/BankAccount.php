<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
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
        'name',
        'tenant_id',
        'type', // 'bank' or 'cash'
        'bank_name',
        'branch_name',
        'iban',
        'currency_id',
        'opening_balance',
        'opening_balance_date',
        'is_active',
        'created_by', // Assuming standard
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate current balance based on opening balance and payments.
     * Payments are strictly SUM(original_amount).
     * Filter by date if opening_balance_date is set.
     */
    public function getBalanceAttribute(): float
    {
        $query = $this->payments();

        if ($this->opening_balance_date) {
            $query->where('payment_date', '>', $this->opening_balance_date);
        }

        // We sum 'original_amount' because that represents the amount in this account's currency
        $totalIn = $query->sum('original_amount');

        return (float) $this->opening_balance + $totalIn;
    }
}
