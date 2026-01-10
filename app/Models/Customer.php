<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'name',
        'phone',
        'email',
        'address',
        'notes',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }
}
