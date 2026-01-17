<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EntitlementsService
{
    /**
     * Get the number of tenants currently used by the account.
     */
    public function accountTenantUsage(Account $account): int
    {
        return $account->tenants()->count();
    }

    /**
     * Get the tenant limit for the account.
     * Returns null if unlimited.
     */
    public function accountTenantLimit(Account $account): ?int
    {
        return $account->effectiveTenantLimit();
    }

    /**
     * Check if the account can create a new tenant.
     */
    public function canCreateTenant(Account $account): bool
    {
        $limit = $this->accountTenantLimit($account);
        
        if ($limit === null) {
            return true;
        }

        return $this->accountTenantUsage($account) < $limit;
    }

    /**
     * Get the number of seats currently used by the account.
     * Logic: Distinct user_ids across all tenants + Unique Pending Invites
     */
    public function accountSeatUsage(Account $account): int
    {
        // 1. Active Users (Distinct users across all tenants of this account)
        // Join tenants -> tenant_user
        $activeSeats = DB::table('tenant_user')
            ->join('tenants', 'tenant_user.tenant_id', '=', 'tenants.id')
            ->where('tenants.account_id', $account->id)
            ->distinct()
            ->count('tenant_user.user_id');

        // 2. Pending Invites (if enabled)
        $pendingSeats = 0;
        if (config('entitlements.count_pending_invites_as_seats', true)) {
            $pendingSeats = $this->countPendingInvitesAsSeats($account);
        }

        return $activeSeats + $pendingSeats;
    }

    /**
     * Count valid pending invites that should consume a seat.
     * - Distinct by email.
     * - Exclude emails that are already active users in this account.
     */
    public function countPendingInvitesAsSeats(Account $account): int
    {
        // Get all tenant IDs for this account
        $tenantIds = $account->tenants()->pluck('id');

        if ($tenantIds->isEmpty()) {
            return 0;
        }

        // Get unique pending invite emails
        $pendingEmails = TenantInvitation::whereIn('tenant_id', $tenantIds)
            ->valid() // Use scopeValid to exclude expired/accepted invites
            ->distinct()
            ->pluck('email')
            ->map(fn($email) => strtolower($email));

        if ($pendingEmails->isEmpty()) {
            return 0;
        }

        // Get existing user emails in this account
        $existingUserEmails = DB::table('users')
            ->join('tenant_user', 'users.id', '=', 'tenant_user.user_id')
            ->join('tenants', 'tenant_user.tenant_id', '=', 'tenants.id')
            ->where('tenants.account_id', $account->id)
            ->distinct()
            ->pluck('users.email')
            ->map(fn($email) => strtolower($email))
            ->toArray();

        // Filter out pending emails that are already users
        $uniqueNewSeats = $pendingEmails->reject(function ($email) use ($existingUserEmails) {
            return in_array($email, $existingUserEmails);
        });

        return $uniqueNewSeats->count();
    }

    /**
     * Get the seat limit for the account.
     * Returns null if unlimited.
     */
    public function accountSeatLimit(Account $account): ?int
    {
        return $account->effectiveSeatLimit();
    }

    /**
     * Check if a new seat can be added to the account.
     * 
     * @param Account $account
     * @param string|null $invitedEmail If provided, checks if this specific email would actually consume a NEW seat.
     * @return bool
     */
    public function canAddSeat(Account $account, ?string $invitedEmail = null): bool
    {
        $limit = $this->accountSeatLimit($account);

        if ($limit === null) {
            return true;
        }

        // Optimization: If email is provided, check if it's already a seat.
        // If the user already consumes a seat (is active in another tenant of same account),
        // or is already counted as pending, then adding them again doesn't increase count.
        // However, "Limit is full" means we can't add NEW people.
        
        $currentUsage = $this->accountSeatUsage($account);

        if ($currentUsage < $limit) {
            return true;
        }

        // Limit is reached or exceeded.
        // Only allow if the operation DOES NOT increase the seat count.
        
        if ($invitedEmail) {
            $normalizedEmail = strtolower($invitedEmail);
            
            // Check if this email is already an active user in this account
            $isExistingUser = DB::table('users')
                ->join('tenant_user', 'users.id', '=', 'tenant_user.user_id')
                ->join('tenants', 'tenant_user.tenant_id', '=', 'tenants.id')
                ->where('tenants.account_id', $account->id)
                ->where(DB::raw('LOWER(users.email)'), $normalizedEmail)
                ->exists();

            if ($isExistingUser) {
                return true; // Already has a seat, so OK to add to another tenant.
            }

                if (config('entitlements.count_pending_invites_as_seats', true)) {
                    $tenantIds = $account->tenants()->pluck('id');
                    $isExistingPending = TenantInvitation::whereIn('tenant_id', $tenantIds)
                        ->where(DB::raw('LOWER(email)'), $normalizedEmail)
                        ->valid() // Use valid() to exclude expired
                        ->exists();
                    
                    if ($isExistingPending) {
                        return true; // Already accounted for in pending seats.
                    }
                }
        }

        return false;
    }
    
    /**
     * Sync account_users table for a user.
     * Ensures user is listed in account_users if they are a member of any tenant.
     */
    public function syncAccountUser(Account $account, User $user, string $role = 'member'): void
    {
        $existing = DB::table('account_users')
            ->where('account_id', $account->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            // Already exists. Update timestamp.
            // DO NOT downgrade role if they are 'owner' or 'billing_admin' and we are trying to set 'member'.
            // Only update role if current is null/empty.
            $updates = ['updated_at' => now()];
            
            if (empty($existing->role)) {
                $updates['role'] = $role;
            }
            
            DB::table('account_users')
                ->where('id', $existing->id)
                ->update($updates);
        } else {
            // Insert new
            DB::table('account_users')->insert([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
