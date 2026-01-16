<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderProgress extends Model
{
    use HasFactory;

    protected $table = 'work_order_progress';

    protected $fillable = [
        'work_order_id',
        'label',
        'progress_percent',
        'updated_by',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
