<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VesselOwnerHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_id',
        'old_customer_id',
        'new_customer_id',
        'changed_by',
        'changed_at',
        'note',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function oldCustomer()
    {
        return $this->belongsTo(Customer::class, 'old_customer_id');
    }

    public function newCustomer()
    {
        return $this->belongsTo(Customer::class, 'new_customer_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
