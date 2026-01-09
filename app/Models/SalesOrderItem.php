<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'section',
        'item_type',
        'description',
        'qty',
        'unit',
        'unit_price',
        'discount_amount',
        'vat_rate',
        'is_optional',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
