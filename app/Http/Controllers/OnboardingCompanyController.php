<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingCompanyController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        // PR14d: Prevent Trial Abuse (Create Page Guard)
        // Same logic as store - prevent even seeing the form
        if (! $user->is_admin) {
            $tenantAccountIds = DB::table('tenant_user')
                ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
                ->where('tenant_user.user_id', $user->id)
                ->pluck('tenants.account_id');
            $ownedAccountIds = Account::where('owner_user_id', $user->id)->pluck('id');
            
            $allAccountIds = $tenantAccountIds->concat($ownedAccountIds)->unique()->filter();
            
            $hasActiveAccount = false;
            if ($allAccountIds->isNotEmpty()) {
                $hasActiveAccount = Account::whereIn('id', $allAccountIds)
                ->where(function($query) {
                    $query->whereNotNull('billing_subscription_id')
                          ->orWhereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                })
                ->exists();
            }

            $hasAnyOwnedAccount = $ownedAccountIds->isNotEmpty();

            if ($hasAnyOwnedAccount && ! $hasActiveAccount) {
                return redirect()->route('billing.paywall')
                    ->with('error', 'Deneme süreniz sona erdi. Yeni firma oluşturmak için mevcut paketinizi yükseltmelisiniz.');
            }
        }

        return view('onboarding.company.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:120',
        ]);

        $user = auth()->user();

        // PR14d: Prevent Trial Abuse (Store Action Guard)
        if (! $user->is_admin) {
            // 1. Check for Active Account Associations
            $tenantAccountIds = DB::table('tenant_user')
                ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
                ->where('tenant_user.user_id', $user->id)
                ->pluck('tenants.account_id');
            $ownedAccountIds = Account::where('owner_user_id', $user->id)->pluck('id');
            
            $allAccountIds = $tenantAccountIds->concat($ownedAccountIds)->unique()->filter();
            
            $hasActiveAccount = false;
            if ($allAccountIds->isNotEmpty()) {
                $hasActiveAccount = Account::whereIn('id', $allAccountIds)
                ->where(function($query) {
                    $query->whereNotNull('billing_subscription_id')
                          ->orWhereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                })
                ->exists();
            }

            // 2. Check for Previous Ownership
            $hasAnyOwnedAccount = $ownedAccountIds->isNotEmpty();

            if ($hasAnyOwnedAccount && ! $hasActiveAccount) {
                return redirect()->route('billing.paywall')
                    ->with('error', 'Deneme süreniz sona erdi. Yeni firma oluşturmak için mevcut paketinizi yükseltmelisiniz.');
            }
        }

        try {
            // PR14d.1: Optimization - Pass user to closure
            DB::transaction(function () use ($request, $user) {
                // 1) Plan Check
                $plan = Plan::where('key', 'starter')->first();
                if (! $plan) {
                    throw ValidationException::withMessages([
                        'name' => 'Başlangıç paketi (starter) bulunamadı. Lütfen sistem yöneticisi ile iletişime geçin veya plan seed işlemini çalıştırın.',
                    ]);
                }

                // 2) Account Create
                $account = Account::create([
                    'owner_user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'plan_key' => 'starter', // Legacy/Redundant but robust
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays(7), // Trial Period
                ]);

                // 3) Tenant Create
                $name = $request->input('name');
                $slug = Str::slug($name);
                
                // Collision resolution
                $originalSlug = $slug;
                $i = 2;
                while (Tenant::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $i;
                    $i++;
                }

                $domain = $slug . '.test'; // Local convention, or from config
                // Ideally this should use config('tenancy.central_domains')[0] etc but strict request says ".test" or suffix
                // We will stick to prompt: "{$slug}.test" (collision varsa domain de suffix'li olsun -> slug is already suffixed)
                
                $tenant = Tenant::create([
                    'account_id' => $account->id,
                    'name' => $name,
                    'slug' => $slug,
                    'domain' => $domain,
                    'is_active' => true,
                ]);

                // 4) Pivots
                // Account Pivot
                DB::table('account_users')->insert([
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'role' => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Tenant Pivot
                DB::table('tenant_user')->insert([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 5) Session
                session(['current_tenant_id' => $tenant->id]);
            });
        } catch (\Exception $e) {
            // Rethrow validation exceptions
            if ($e instanceof ValidationException) {
                throw $e;
            }
            
            // Log if needed, but return generic error
            throw ValidationException::withMessages([
                'name' => 'Firma oluşturulurken bir hata oluştu: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Firma ve hesap başarıyla oluşturuldu.');
    }
}
