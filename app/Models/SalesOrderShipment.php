<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'warehouse_id',
        'invoice_id',
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
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
        return $this->hasMany(SalesOrderShipmentLine::class);
    }
    public function returns()
    {
        return $this->hasMany(SalesOrderReturn::class)->orderByDesc('created_at');
    }
}
