<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

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
        'stock_posted_at',
        'stock_posted_warehouse_id',
        'stock_posted_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'stock_posted_at' => 'datetime',
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

    public function stockPostedWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'stock_posted_warehouse_id');
    }

    public function stockPostedBy()
    {
        return $this->belongsTo(User::class, 'stock_posted_by');
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class)->orderBy('sort_order');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class)->where('is_current', true);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class)->orderByDesc('revision_no');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public static function statusTransitions(): array
    {
        return [
            'draft' => ['confirmed', 'cancelled', 'contracted'],
            'confirmed' => ['in_progress', 'cancelled', 'contracted'],
            'in_progress' => ['completed', 'cancelled', 'contracted'],
            'completed' => ['contracted'],
            'contracted' => [],
            'cancelled' => [],
        ];
    }

    public function canTransitionTo(string $next): bool
    {
        if ($next === $this->status) {
            return true;
        }

        $transitions = self::statusTransitions();

        return in_array($next, $transitions[$this->status] ?? [], true);
    }

    public function transitionTo(string $next, array $meta = [], ?int $actorId = null): bool
    {
        $from = $this->status;

        if (! $this->canTransitionTo($next)) {
            return false;
        }

        if ($from === $next) {
            return true;
        }

        $this->forceFill(['status' => $next])->save();

        app(ActivityLogger::class)->log($this, 'status_changed', array_merge($meta, [
            'from' => $from,
            'to' => $next,
        ]), $actorId);

        return true;
    }

    public function isLocked(): bool
    {
        // Kilit yalnızca gerçekten bağlı sözleşme varsa aktif olmalı.
        if ($this->relationLoaded('contract')) {
            return $this->contract !== null;
        }

        return $this->contract()->exists();
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');

        $calculator = new \App\Services\TotalsCalculator();
        $totals = $calculator->calculate($this->items);

        $this->forceFill([
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'vat_total' => $totals['vat_total'],
            'grand_total' => $totals['grand_total'],
        ])->save();
    }
    public function followUps()
    {
        return $this->morphMany(\App\Models\FollowUp::class, 'subject')->latest('next_at');
    }

    public function openFollowUps()
    {
        return $this->followUps()->whereNull('completed_at')->orderBy('next_at');
    }
    public function shipments()
    {
        return $this->hasMany(SalesOrderShipment::class)->orderByDesc('created_at');
    }

    public function returns()
    {
        return $this->hasMany(SalesOrderReturn::class)->orderByDesc('created_at');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class)->orderByDesc('created_at');
    }
}
