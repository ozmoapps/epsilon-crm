<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vessel_id',
        'work_order_id',
        'title',
        'status',
        'currency',
        'validity_days',
        'estimated_duration_days',
        'payment_terms',
        'warranty_text',
        'exclusions',
        'notes',
        'fx_note',
        'sent_at',
        'accepted_at',
        'created_by',
        'subtotal',
        'discount_total',
        'vat_total',
        'grand_total',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if ($quote->quote_no) {
                return;
            }

            $year = now()->year;
            $prefix = config('quotes.prefix');
            $padding = config('quotes.padding');

            DB::transaction(function () use ($quote, $year, $prefix, $padding) {
                $sequence = QuoteSequence::lockForUpdate()->find($year);

                if (! $sequence) {
                    $sequence = QuoteSequence::create([
                        'year' => $year,
                        'last_number' => 0,
                    ]);
                }

                $sequence->last_number += 1;
                $sequence->save();

                $quote->quote_no = sprintf('%s-%s-%0' . $padding . 'd', $prefix, $year, $sequence->last_number);
            });
        });
    }

    public static function statusOptions(): array
    {
        return config('quotes.statuses', []);
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = self::statusOptions();

        return $statuses[$this->status] ?? $this->status;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'quote_id');
    }

    public function isLocked(): bool
    {
        if ($this->relationLoaded('salesOrder')) {
            return $this->salesOrder !== null;
        }

        return $this->salesOrder()->exists();
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');

        $subtotal = 0;
        $discountTotal = 0;
        $vatTotal = 0;
        $grandTotal = 0;

        $this->items
            ->where('is_optional', false)
            ->each(function (QuoteItem $item) use (&$subtotal, &$discountTotal, &$vatTotal, &$grandTotal) {
                $qty = (float) $item->qty;
                $unitPrice = (float) $item->unit_price;
                $lineBase = $qty * $unitPrice;
                $lineDiscount = (float) ($item->discount_amount ?? 0);
                $lineNet = max($lineBase - $lineDiscount, 0);
                $vatRate = $item->vat_rate !== null ? (float) $item->vat_rate : null;
                $lineVat = $vatRate !== null ? $lineNet * ($vatRate / 100) : 0;
                $lineTotal = $lineNet + $lineVat;

                $subtotal += $lineBase;
                $discountTotal += $lineDiscount;
                $vatTotal += $lineVat;
                $grandTotal += $lineTotal;
            });

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'vat_total' => $vatTotal,
            'grand_total' => $grandTotal,
        ])->save();
    }
}
