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
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
