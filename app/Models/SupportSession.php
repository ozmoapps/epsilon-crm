<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSession extends Model
{
    protected $fillable = [
        'tenant_id',
        'requested_by_user_id',
        'token_hash',
        'approved_at',
        'expires_at',
        'used_at',
        'revoked_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && $this->revoked_at === null;
    }
}
