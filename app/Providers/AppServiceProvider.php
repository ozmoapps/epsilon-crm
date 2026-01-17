<?php

namespace App\Providers;

use App\Models\ContractTemplate;
use App\Models\Contract;
use App\Models\CompanyProfile;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Observers\ActivityLogObserver;
use App\Policies\CompanyProfilePolicy;
use App\Policies\BankAccountPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\ContractPolicy;
use App\Policies\ContractTemplatePolicy;
use App\Policies\QuotePolicy;
use App\Policies\SalesOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // PR3C10: Removed blanket Gate::before(true).
        // Policies should strictly enforce logic based on TenantContext and Membership.
        // Gate::before(function ($user, $ability) { return true; }); -> REMOVED


        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(ContractTemplate::class, ContractTemplatePolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(CompanyProfile::class, CompanyProfilePolicy::class);
        Gate::policy(BankAccount::class, BankAccountPolicy::class);
        Gate::policy(Currency::class, CurrencyPolicy::class);

        Quote::observe(ActivityLogObserver::class);
        SalesOrder::observe(ActivityLogObserver::class);
        Contract::observe(ActivityLogObserver::class);
        \App\Models\Customer::observe(ActivityLogObserver::class);
        \App\Models\Vessel::observe(ActivityLogObserver::class);
        \App\Models\Vessel::observe(\App\Observers\VesselOwnerHistoryObserver::class);

        // PR3C9: Tenant Scoped Route Model Binding
        // If a tenant context is active, force binding to check tenant_id.
        // This prevents cross-tenant access via ID guessing even if policies fail.
        $tenantScopedModels = [
            'customer' => \App\Models\Customer::class,
            'vessel' => \App\Models\Vessel::class,
            'work_order' => \App\Models\WorkOrder::class,
            'quote' => \App\Models\Quote::class,
            'sales_order' => \App\Models\SalesOrder::class,
            'invoice' => \App\Models\Invoice::class,
            'payment' => \App\Models\Payment::class,
            'bank_account' => \App\Models\BankAccount::class,
            'product' => \App\Models\Product::class,
            'warehouse' => \App\Models\Warehouse::class,
            'stock_movement' => \App\Models\StockMovement::class,
            'stock_transfer' => \App\Models\StockTransfer::class,
            'stock_operation' => \App\Models\StockOperation::class,
        ];

        foreach ($tenantScopedModels as $key => $class) {
            \Illuminate\Support\Facades\Route::bind($key, function ($value) use ($class) {
                $query = $class::query();
                
                // Check if we are in a tenant context
                $context = app(\App\Services\TenantContext::class);
                if ($tenant = $context->getTenant()) {
                    // Check if model actually has tenant_id column before applying (Safety)
                    // But these are known tenant models.
                    $query->where('tenant_id', $tenant->id);
                }

                // Standard findOrFail
                return $query->where((new $class)->getRouteKeyName(), $value)->firstOrFail();
            });
        }
    }
}
