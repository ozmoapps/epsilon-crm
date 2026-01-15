<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VesselContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_id',
        'role',
        'name',
        'phone',
        'email',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
