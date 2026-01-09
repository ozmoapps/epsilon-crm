<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'root_contract_id',
        'revision_no',
        'superseded_by_id',
        'superseded_at',
        'is_current',
        'contract_template_id',
        'contract_template_version_id',
        'contract_no',
        'status',
        'issued_at',
        'signed_at',
        'locale',
        'currency',
        'customer_name',
        'customer_company',
        'customer_tax_no',
        'customer_address',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax_total',
        'grand_total',
        'payment_terms',
        'warranty_terms',
        'scope_text',
        'exclusions_text',
        'delivery_terms',
        'rendered_body',
        'rendered_at',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'signed_at' => 'datetime',
        'rendered_at' => 'datetime',
        'superseded_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'is_current' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'draft',
        'locale' => 'tr',
        'revision_no' => 1,
        'is_current' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Contract $contract) {
            if ($contract->contract_no) {
                return;
            }

            $year = now()->year;
            $prefix = config('contracts.prefix');
            $padding = config('contracts.padding');

            DB::transaction(function () use ($contract, $year, $prefix, $padding) {
                $sequence = ContractSequence::lockForUpdate()->find($year);

                if (! $sequence) {
                    $sequence = ContractSequence::create([
                        'year' => $year,
                        'last_number' => 0,
                    ]);
                }

                $sequence->last_number += 1;
                $sequence->save();

                $contract->contract_no = sprintf('%s-%s-%0' . $padding . 'd', $prefix, $year, $sequence->last_number);
            });
        });
    }

    public static function statusOptions(): array
    {
        return config('contracts.statuses', []);
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = self::statusOptions();

        return $statuses[$this->status] ?? $this->status;
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function rootContract()
    {
        return $this->belongsTo(self::class, 'root_contract_id');
    }

    public function revisions()
    {
        return $this->hasMany(self::class, 'root_contract_id');
    }

    public function supersededBy()
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contractTemplate()
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    public function contractTemplateVersion()
    {
        return $this->belongsTo(ContractTemplateVersion::class);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isRoot(): bool
    {
        return $this->root_contract_id === null;
    }

    public function getRevisionLabelAttribute(): string
    {
        return 'R' . ($this->revision_no ?? 1);
    }

    public function canCreateRevision(): bool
    {
        return $this->is_current && in_array($this->status, ['draft', 'sent', 'signed'], true);
    }
}
