<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\TenantScoped;

class WorkOrder extends Model
{
    use HasFactory, TenantScoped;

    public const STATUS_OPTIONS = [
        'draft' => 'Taslak',
        'planned' => 'Planlandı',
        'started' => 'Başladı',
        'in_progress' => 'Devam Ediyor',
        'on_hold' => 'Beklemede',
        'completed' => 'Tamamlandı',
        'delivered' => 'Teslim Edildi',
        'cancelled' => 'İptal',
    ];

    protected $fillable = [
        'created_by',
        'tenant_id',
        'customer_id',
        'vessel_id',
        'title',
        'description',
        'status',
        'planned_start_at',
        'planned_end_at',
        'delivered_at',
        'delivered_by',
        'delivered_to_name',
        'delivery_notes',
    ];


    public function items()
    {
        return $this->hasMany(WorkOrderItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->tenant_id && app(\App\Services\TenantContext::class)->id()) {
                $model->tenant_id = app(\App\Services\TenantContext::class)->id();
            }
        });
    }

    protected $casts = [
        'planned_start_at' => 'date',
        'planned_end_at' => 'date',
        'delivered_at' => 'datetime',
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

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function photos()
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    public function updates()
    {
        return $this->hasMany(WorkOrderUpdate::class)->latest('happened_at');
    }

    public function progress()
    {
        return $this->hasMany(WorkOrderProgress::class)->latest();
    }
}
