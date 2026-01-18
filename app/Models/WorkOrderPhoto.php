<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\TenantScoped;

class WorkOrderPhoto extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'type',
        'path',
        'caption',
        'uploaded_by',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
