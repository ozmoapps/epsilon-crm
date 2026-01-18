<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class Quote extends Model
{
    use HasFactory;
    use \App\Models\Concerns\TenantScoped;

    protected $fillable = [
        'created_by',
        'tenant_id',
        'customer_id',
        'vessel_id',
        'work_order_id',
        'sales_order_id',
        'title',
        'status',
        'issued_at',
        'converted_at',
        'contact_name', 
        'contact_phone',
        'location',
        'currency_id',
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
        'converted_at' => 'datetime',
        'issued_at' => 'date',
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
            if (! $quote->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $quote->tenant_id = app(\App\Services\TenantContext::class)->id();
            }

            if (! $quote->currency_id) {
                $quote->currency_id = self::resolveDefaultCurrencyId();
            }

            if (! $quote->currency && $quote->currency_id) {
                $quote->currency = Currency::query()
                    ->whereKey($quote->currency_id)
                    ->value('code') ?? $quote->currency;
            }

            if ($quote->quote_no) {
                return;
            }

            $year = now()->year;
            $prefix = config('quotes.prefix');
            $padding = config('quotes.padding');

            DB::transaction(function () use ($quote, $year, $prefix, $padding) {
                $tenantId = $quote->tenant_id;
                // Lock specifically for this tenant's year sequence
                $sequence = QuoteSequence::lockForUpdate()
                    ->where('tenant_id', $tenantId)
                    ->where('year', $year)
                    ->first();

                if (! $sequence) {
                    $sequence = QuoteSequence::create([
                        'tenant_id' => $tenantId,
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

    public static function resolveDefaultCurrencyId(): ?int
    {
        $query = Currency::query()->where('is_active', true);
        $defaultCode = config('company.default_code');

        if ($defaultCode) {
            $defaultId = (clone $query)->where('code', $defaultCode)->value('id');

            if ($defaultId) {
                return $defaultId;
            }
        }

        return $query->orderBy('id')->value('id');
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

    public function currencyRelation(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
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

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public static function statusTransitions(): array
    {
        return [
            'draft' => ['sent', 'cancelled'],
            'sent' => ['accepted', 'cancelled'],
            'accepted' => ['converted', 'cancelled'],
            'converted' => [],
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
        // Kilit yalnızca gerçekten bağlı SalesOrder varsa aktif olmalı
        if ($this->relationLoaded('salesOrder')) {
            return $this->salesOrder !== null;
        }

        return $this->salesOrder()->exists();
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
}
