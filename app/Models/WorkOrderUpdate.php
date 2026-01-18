<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\TenantScoped;

class WorkOrderUpdate extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'note',
        'photo_path',
        'created_by',
        'happened_at',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
