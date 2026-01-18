<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Concerns\TenantScoped;

class WorkOrderItem extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'product_id',
        'description',
        'qty',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'product_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
