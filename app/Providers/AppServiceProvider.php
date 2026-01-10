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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}
