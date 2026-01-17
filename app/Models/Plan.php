<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'key',
        'name_tr',
        'tenant_limit',
        'seat_limit',
        'extra_seat_price_cents',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tenant_limit' => 'integer',
        'seat_limit' => 'integer',
        'extra_seat_price_cents' => 'integer',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
