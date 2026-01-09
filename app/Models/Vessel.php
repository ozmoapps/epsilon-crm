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
        'boat_type',
        'material',
        'loa_m',
        'beam_m',
        'draft_m',
        'net_tonnage',
        'gross_tonnage',
        'passenger_capacity',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function getBoatTypeLabelAttribute(): ?string
    {
        if (! $this->boat_type) {
            return null;
        }

        $boatTypes = config('vessels.boat_types', []);

        return $boatTypes[$this->boat_type] ?? $this->boat_type;
    }

    public function getMaterialLabelAttribute(): ?string
    {
        if (! $this->material) {
            return null;
        }

        $materials = config('vessels.materials', []);

        return $materials[$this->material] ?? $this->material;
    }
}
