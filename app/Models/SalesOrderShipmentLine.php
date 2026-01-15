<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderShipmentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_shipment_id',
        'sales_order_item_id',
        'product_id',
        'description',
        'qty',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function shipment()
    {
        return $this->belongsTo(SalesOrderShipment::class, 'sales_order_shipment_id');
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnLines()
    {
        return $this->hasMany(SalesOrderReturnLine::class, 'sales_order_shipment_line_id');
    }
}
