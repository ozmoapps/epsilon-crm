<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'owner_user_id',
        'plan_id',
        'status',
        'extra_seats_purchased',
        'billing_provider',
        'billing_customer_id',
        'billing_subscription_id',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'extra_seats_purchased' => 'integer',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'account_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get the effective seat limit (Plan limit + Extra purchases).
     * Returns null if unlimited.
     */
    public function effectiveSeatLimit(): ?int
    {
        if ($this->plan->seat_limit === null) {
            return null;
        }

        return $this->plan->seat_limit + $this->extra_seats_purchased;
    }

    /**
     * Get the effective tenant limit.
     * Returns null if unlimited.
     */
    public function effectiveTenantLimit(): ?int
    {
        return $this->plan->tenant_limit;
    }
}
