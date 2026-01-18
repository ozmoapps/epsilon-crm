<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['account_id', 'name', 'slug', 'domain', 'is_active'];

    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (empty($tenant->slug)) {
                $slug = \Illuminate\Support\Str::slug($tenant->name);
                if (empty($slug)) {
                    $slug = 'tenant-' . uniqid();
                }
                
                // Simple collision resolution
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $count++;
                    $slug = $originalSlug . '-' . $count;
                }
                
                $tenant->slug = $slug;
            }

            // Ensure account_id is set (backfill guard)
            if (empty($tenant->account_id)) {
                $user = auth()->user();
                if ($user) {
                    // 1. Try to find an account owned by this user
                    $account = \App\Models\Account::where('owner_user_id', $user->id)->first();
                    
                    if (!$account) {
                        // 2. Create a new account for this user (Starter plan)
                        $plan = \App\Models\Plan::where('key', 'starter')->first();
                        $planId = $plan ? $plan->id : null;
                        
                        $account = \App\Models\Account::create([
                            'owner_user_id' => $user->id,
                            'plan_key' => 'starter',
                            'plan_id' => $planId,
                            'status' => 'active',
                            'name' => $user->name . ' Account',
                        ]);

                        // Ensure user is attached as owner
                        $account->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
                    }
                    
                    $tenant->account_id = $account->id;
                }
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function plan()
    {
        return $this->hasOneThrough(Plan::class, Account::class, 'id', 'id', 'account_id', 'plan_id');
    }
}
