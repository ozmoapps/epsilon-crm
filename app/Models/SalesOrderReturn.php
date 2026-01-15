<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'sales_order_shipment_id',
        'warehouse_id',
        'status',
        'posted_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function shipment()
    {
        return $this->belongsTo(SalesOrderShipment::class, 'sales_order_shipment_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(SalesOrderReturnLine::class);
    }
}
