<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantInvitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'email',
        'token_hash',
        'role',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function scopeValid($query)
    {
        return $query->whereNull('accepted_at')
                     ->where('expires_at', '>', now());
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at');
    }

    public static function generateLink(Tenant $tenant, string $token): string
    {
        $link = url('/invite/' . $token);
        
        // Domain Aware Link Generation
        if (config('tenancy.resolve_by_domain')) {
            if ($tenant->domain) {
                // If tenant has custom domain
                $scheme = request()->getScheme();
                $link = $scheme . '://' . $tenant->domain . '/invite/' . $token;
            } elseif ($baseDomain = config('tenancy.base_domain')) {
                // If tenant has slug and base domain is configured (subdomain mode)
                // and tenant has a slug (it should)
                if ($tenant->slug) {
                    $scheme = request()->getScheme();
                    $link = $scheme . '://' . $tenant->slug . '.' . $baseDomain . '/invite/' . $token;
                }
            }
        }
        
        return $link;
    }
}
