<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;

    public const STATUS_OPTIONS = [
        'draft' => 'Taslak',
        'planned' => 'Planlandı',
        'in_progress' => 'Devam Ediyor',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal',
    ];

    protected $fillable = [
        'created_by',
        'customer_id',
        'vessel_id',
        'title',
        'description',
        'status',
        'planned_start_at',
        'planned_end_at',
    ];


    public function items()
    {
        return $this->hasMany(WorkOrderItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $casts = [
        'planned_start_at' => 'date',
        'planned_end_at' => 'date',
    ];

    public static function statusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }
    public function followUps()
    {
        return $this->morphMany(\App\Models\FollowUp::class, 'subject')->latest('next_at');
    }

    public function openFollowUps()
    {
        return $this->followUps()->whereNull('completed_at')->orderBy('next_at');
    }

    public function stockPostedWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'stock_posted_warehouse_id');
    }

    public function stockPostedBy()
    {
        return $this->belongsTo(User::class, 'stock_posted_by');
    }
}
