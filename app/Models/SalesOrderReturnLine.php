<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderReturnLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_return_id',
        'sales_order_shipment_line_id',
        'product_id',
        'qty',
        'description',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function return()
    {
        return $this->belongsTo(SalesOrderReturn::class, 'sales_order_return_id');
    }

    public function shipmentLine()
    {
        return $this->belongsTo(SalesOrderShipmentLine::class, 'sales_order_shipment_line_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
