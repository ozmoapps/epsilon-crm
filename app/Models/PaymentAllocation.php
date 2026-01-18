<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\TenantScoped;

class PaymentAllocation extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'payment_id',
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
