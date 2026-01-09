<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vessel_id',
        'work_order_id',
        'quote_id',
        'order_no',
        'title',
        'status',
        'currency',
        'order_date',
        'delivery_place',
        'delivery_days',
        'payment_terms',
        'warranty_text',
        'exclusions',
        'notes',
        'fx_note',
        'subtotal',
        'discount_total',
        'vat_total',
        'grand_total',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_days' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'EUR',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $salesOrder) {
            if ($salesOrder->order_no) {
                return;
            }

            $year = now()->year;
            $prefix = config('sales_orders.prefix');
            $padding = config('sales_orders.padding');

            DB::transaction(function () use ($salesOrder, $year, $prefix, $padding) {
                $sequence = SalesOrderSequence::lockForUpdate()->find($year);

                if (! $sequence) {
                    $sequence = SalesOrderSequence::create([
                        'year' => $year,
                        'last_number' => 0,
                    ]);
                }

                $sequence->last_number += 1;
                $sequence->save();

                $salesOrder->order_no = sprintf('%s-%s-%0' . $padding . 'd', $prefix, $year, $sequence->last_number);
            });
        });
    }

    public static function statusOptions(): array
    {
        return config('sales_orders.statuses', []);
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

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class)->orderBy('sort_order');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
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
            ->each(function (SalesOrderItem $item) use (&$subtotal, &$discountTotal, &$vatTotal, &$grandTotal) {
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
