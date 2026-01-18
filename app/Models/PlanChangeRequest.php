<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanChangeRequest extends Model
{
    use HasFactory;
    
    // Note: NOT using TenantScoped because Admin needs to see all requests.
    // Tenant filtering will be explicit where needed.

    protected $fillable = [
        'account_id',
        'tenant_id',
        'requested_by_user_id',
        'current_plan_key',
        'requested_plan_key',
        'reason',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
