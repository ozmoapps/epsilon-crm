<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'type',
        'registration_number',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
