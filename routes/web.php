<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractAttachmentController;
use App\Http\Controllers\ContractDeliveryController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteItemController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesOrderItemController;
use App\Http\Controllers\VesselController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Onboarding Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding/company', [App\Http\Controllers\OnboardingCompanyController::class, 'create'])->name('onboarding.company.create');
    Route::post('/onboarding/company', [App\Http\Controllers\OnboardingCompanyController::class, 'store'])->name('onboarding.company.store');
});

// PR5a: Membership-first Tenancy Routes
Route::middleware(['auth'])->prefix('manage/tenants')->name('manage.tenants.')->group(function () {
    Route::get('join', function () {
        return view('manage.tenants.join');
    })->name('join');
    
    Route::get('select', function () {
        // Redirect logic handled in SetTenant or user can select manually
        return view('manage.tenants.select');
    })->name('select');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/saved-views', [App\Http\Controllers\SavedViewController::class, 'index'])->name('saved-views.index');
    Route::post('/saved-views', [App\Http\Controllers\SavedViewController::class, 'store'])->name('saved-views.store');
    Route::delete('/saved-views/{savedView}', [App\Http\Controllers\SavedViewController::class, 'destroy'])->name('saved-views.destroy');

    Route::delete('/saved-views/{savedView}', [App\Http\Controllers\SavedViewController::class, 'destroy'])->name('saved-views.destroy');

    Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDestroy'])->name('customers.bulk_destroy');

    // PR14c: Paywall / Billing Placeholder
    Route::get('/billing', function () {
        return view('billing.paywall');
    })->name('billing.paywall');

    // Core Resources (Accessible to Staff)
    Route::resource('customers', CustomerController::class);
    Route::resource('vessels', VesselController::class);
    
    Route::resource('work-orders', WorkOrderController::class);
    Route::post('work-orders/{workOrder}/items', [App\Http\Controllers\WorkOrderItemController::class, 'store'])->name('work-orders.items.store');
    Route::put('work-orders/{workOrder}/items/{item}', [App\Http\Controllers\WorkOrderItemController::class, 'update'])->name('work-orders.items.update');
    Route::delete('work-orders/{workOrder}/items/{item}', [App\Http\Controllers\WorkOrderItemController::class, 'destroy'])->name('work-orders.items.destroy');
    Route::post('work-orders/{workOrder}/post-stock', [WorkOrderController::class, 'postStock'])->name('work-orders.post-stock');
    Route::get('work-orders/{workOrder}/print', [WorkOrderController::class, 'printView'])->name('work-orders.print');
    
    Route::post('work-orders/{workOrder}/photos', [App\Http\Controllers\WorkOrderPhotoController::class, 'store'])->name('work-orders.photos.store');
    Route::delete('work-order-photos/{photo}', [App\Http\Controllers\WorkOrderPhotoController::class, 'destroy'])->name('work-order-photos.destroy');

    // Sprint O3: Updates & Progress
    Route::post('work-orders/{workOrder}/updates', [App\Http\Controllers\WorkOrderUpdateController::class, 'store'])->name('work-orders.updates.store');
    Route::delete('work-order-updates/{update}', [App\Http\Controllers\WorkOrderUpdateController::class, 'destroy'])->name('work-order-updates.destroy');
    Route::post('work-orders/{workOrder}/progress', [App\Http\Controllers\WorkOrderProgressController::class, 'store'])->name('work-orders.progress.store');
    Route::post('work-orders/{workOrder}/deliver', [App\Http\Controllers\WorkOrderController::class, 'deliver'])->name('work-orders.deliver');

    // Stock Module
    Route::resource('products', App\Http\Controllers\ProductController::class);
    Route::resource('categories', App\Http\Controllers\CategoryController::class)->except(['create', 'edit', 'show']);
    Route::resource('warehouses', App\Http\Controllers\WarehouseController::class)->except(['create', 'edit', 'show']);

    // Stock Ops
    Route::get('/stock/dashboard', [App\Http\Controllers\StockDashboardController::class, 'index'])->name('stock.dashboard');
    Route::get('stock-movements', [App\Http\Controllers\StockMovementController::class, 'index'])->name('stock-movements.index');
    Route::resource('stock-transfers', App\Http\Controllers\StockTransferController::class)->except(['edit', 'update', 'destroy']);
    Route::post('stock-transfers/{stockTransfer}/post', [App\Http\Controllers\StockTransferController::class, 'post'])->name('stock-transfers.post');
    Route::get('stock-operations/create', [App\Http\Controllers\StockOperationController::class, 'create'])->name('stock-operations.create');
    Route::post('stock-operations', [App\Http\Controllers\StockOperationController::class, 'store'])->name('stock-operations.store');

    // Vessel Contacts
    Route::post('vessels/{vessel}/contacts', [App\Http\Controllers\VesselContactController::class, 'store'])->name('vessels.contacts.store');
    Route::delete('vessels/{vessel}/contacts/{contact}', [App\Http\Controllers\VesselContactController::class, 'destroy'])->name('vessels.contacts.destroy');

    // Internal API Lookups
    Route::prefix('api')->group(function () {
        Route::get('customers/{customer}/vessels', [App\Http\Controllers\Api\ApiLookupController::class, 'vesselsByCustomer'])
            ->name('api.customers.vessels');

        Route::get('vessels/search', [App\Http\Controllers\Api\ApiLookupController::class, 'searchVessels'])
            ->name('api.vessels.search');

        Route::get('vessels/{vessel}', [App\Http\Controllers\Api\ApiLookupController::class, 'vesselDetail'])
            ->name('api.vessels.detail')
            ->whereNumber('vessel');
    });
    
    // Financial & Sensitive Routes (Tenant Admin Only)
    Route::middleware(['tenant.admin'])->group(function () {

        // Ledger Routes
        Route::get('customers/{customer}/ledger', [\App\Http\Controllers\CustomerLedgerController::class, 'index'])->name('customers.ledger');
        Route::get('customers/{customer}/ledger/manual/create', [\App\Http\Controllers\CustomerLedgerManualEntryController::class, 'create'])->name('customers.ledger.manual.create');
        Route::post('customers/{customer}/ledger/manual', [\App\Http\Controllers\CustomerLedgerManualEntryController::class, 'store'])->name('customers.ledger.manual.store');
        Route::delete('customers/{customer}/ledger/manual/{entry}', [\App\Http\Controllers\CustomerLedgerManualEntryController::class, 'destroy'])->name('customers.ledger.manual.destroy');
        
        // Global Customer Ledger Index
        Route::get('/customer-ledgers', [\App\Http\Controllers\CustomerLedgerIndexController::class, 'index'])->name('customer-ledgers.index');

        // Contracts (Admin-Only Actions)
        Route::post('contracts/{contract}/attachments', [ContractAttachmentController::class, 'store'])->name('contracts.attachments.store');
        Route::delete('contracts/{contract}/attachments/{attachment}', [ContractAttachmentController::class, 'destroy'])->name('contracts.attachments.destroy');
        Route::post('contracts/{contract}/deliveries', [ContractDeliveryController::class, 'store'])->name('contracts.deliveries.store');
        Route::patch('contracts/{contract}/deliveries/{delivery}/mark-sent', [ContractDeliveryController::class, 'markSent'])->name('contracts.deliveries.mark_sent');
        Route::post('contracts/{contract}/revise', [ContractController::class, 'revise'])->name('contracts.revise');
        Route::patch('contracts/{contract}/mark-sent', [ContractController::class, 'markSent'])->name('contracts.mark_sent');
        Route::patch('contracts/{contract}/mark-signed', [ContractController::class, 'markSigned'])->name('contracts.mark_signed');
        Route::patch('contracts/{contract}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
        
        // Contract Resources (Admin Write)
        Route::resource('contracts', ContractController::class)->only(['edit', 'update', 'destroy']);


        // Quotes (Admin Actions)
        Route::post('quotes/bulk-delete', [QuoteController::class, 'bulkDestroy'])->name('quotes.bulk_destroy');
        Route::resource('quotes', QuoteController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
        Route::post('quotes/{quote}/mark-sent', [QuoteController::class, 'markAsSent'])->name('quotes.mark_sent');
        Route::post('quotes/{quote}/mark-accepted', [QuoteController::class, 'markAsAccepted'])->name('quotes.mark_accepted');
        Route::post('quotes/{quote}/convert-to-sales-order', [QuoteController::class, 'convertToSalesOrder'])->name('quotes.convert_to_sales_order');
        Route::post('quotes/{quote}/items', [QuoteItemController::class, 'store'])->name('quotes.items.store');
        Route::put('quotes/{quote}/items/{item}', [QuoteItemController::class, 'update'])->name('quotes.items.update');
        Route::delete('quotes/{quote}/items/{item}', [QuoteItemController::class, 'destroy'])->name('quotes.items.destroy');


        // Sales Orders (Admin Actions - Create/Edit/Delete Only)
        Route::resource('sales-orders', SalesOrderController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
        Route::get('sales-orders/{salesOrder}/contracts/create', [ContractController::class, 'create'])->name('sales-orders.contracts.create');
        Route::post('sales-orders/{salesOrder}/contracts', [ContractController::class, 'store'])->name('sales-orders.contracts.store');
        Route::post('sales-orders/{salesOrder}/items', [SalesOrderItemController::class, 'store'])->name('sales-orders.items.store');
        Route::put('sales-orders/{salesOrder}/items/{item}', [SalesOrderItemController::class, 'update'])->name('sales-orders.items.update');
        Route::delete('sales-orders/{salesOrder}/items/{item}', [SalesOrderItemController::class, 'destroy'])->name('sales-orders.items.destroy');

        
        Route::post('/follow-ups', [App\Http\Controllers\FollowUpController::class, 'store'])->name('follow-ups.store');
        Route::post('/follow-ups/{followUp}/complete', [App\Http\Controllers\FollowUpController::class, 'complete'])->name('follow-ups.complete');
        Route::delete('/follow-ups/{followUp}', [App\Http\Controllers\FollowUpController::class, 'destroy'])->name('follow-ups.destroy');

        // Invoices & Payments
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('invoices.payments.store');

        Route::resource('bank-accounts', BankAccountController::class);

        Route::get('payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [App\Http\Controllers\PaymentController::class, 'storeAdvance'])->name('payments.store');
    });

    // PR OPS-01: Shared Access (Read-Only for Staff) - Moved here to prevent caching issues (create vs show)
    // Quotes (Read Only)
    Route::resource('quotes', QuoteController::class)->only(['index', 'show']);
    Route::get('quotes/{quote}/preview', [QuoteController::class, 'preview'])->name('quotes.preview');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('quotes/{quote}/print', [QuoteController::class, 'printView'])->name('quotes.print');

    // Sales Orders (Read Only)
    Route::resource('sales-orders', SalesOrderController::class)->only(['index', 'show']);
    Route::get('sales-orders/{salesOrder}/preview', [SalesOrderController::class, 'preview'])->name('sales-orders.preview');
    Route::get('sales-orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])->name('sales-orders.pdf');
    Route::get('sales-orders/{salesOrder}/print', [SalesOrderController::class, 'printView'])->name('sales-orders.print');
    // Shipments/Returns Read Only
    Route::get('sales-orders/{salesOrder}/shipments/{shipment}', [App\Http\Controllers\SalesOrderShipmentController::class, 'show'])->name('sales-orders.shipments.show');
    Route::get('returns/{return}', [App\Http\Controllers\SalesOrderReturnController::class, 'show'])->name('returns.show');

    // PR9: Shared Operational Actions (Accessible to Staff)
    // These actions drive the operation flow
    Route::patch('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
    Route::patch('sales-orders/{salesOrder}/start', [SalesOrderController::class, 'start'])->name('sales-orders.start');
    Route::patch('sales-orders/{salesOrder}/complete', [SalesOrderController::class, 'complete'])->name('sales-orders.complete');
    Route::patch('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
    Route::post('sales-orders/{salesOrder}/post-stock', [SalesOrderController::class, 'postStock'])->name('sales-orders.post-stock');
    Route::post('sales-orders/{salesOrder}/create-work-order', [SalesOrderController::class, 'createWorkOrder'])->name('sales-orders.create-work-order');

    // Shipments (Shared Operational)
    Route::get('sales-orders/{salesOrder}/shipments/create', [App\Http\Controllers\SalesOrderShipmentController::class, 'create'])->name('sales-orders.shipments.create');
    Route::post('sales-orders/{salesOrder}/shipments', [App\Http\Controllers\SalesOrderShipmentController::class, 'store'])->name('sales-orders.shipments.store');
    Route::post('shipments/{shipment}/post', [App\Http\Controllers\SalesOrderShipmentController::class, 'post'])->name('shipments.post');
    Route::delete('shipments/{shipment}', [App\Http\Controllers\SalesOrderShipmentController::class, 'destroy'])->name('shipments.destroy');

    // Contracts (Read Only)
    Route::resource('contracts', ContractController::class)->only(['index', 'show']);
    Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
    Route::get('contracts/{contract}/print', [ContractController::class, 'printView'])->name('contracts.print');
    Route::get('contracts/{contract}/attachments/{attachment}', [ContractAttachmentController::class, 'download'])->name('contracts.attachments.download');
    Route::get('contracts/{contract}/delivery-pack', [ContractDeliveryController::class, 'downloadPack'])->name('contracts.delivery_pack');
    
    // Sprint 3.2: Manual Allocation Removed (Parachute Mode Active)
    // Route::get('payments/{payment}/allocate', [App\Http\Controllers\PaymentController::class, 'allocate'])->name('payments.allocate');
    // Route::post('payments/{payment}/allocations', [App\Http\Controllers\PaymentAllocationController::class, 'store'])->name('payments.allocations.store');
    // Route::post('payments/{payment}/allocations/bulk', [App\Http\Controllers\PaymentAllocationController::class, 'storeBulk'])->name('payments.allocations.bulk');
    // Route::delete('payments/{payment}/allocations/{allocation}', [App\Http\Controllers\PaymentAllocationController::class, 'destroy'])->name('payments.allocations.destroy');

    // Admin Routes
    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        // Settings
        // Settings
            Route::match(['POST', 'PUT'], 'contract-templates/preview', [ContractTemplateController::class, 'preview'])
                ->name('contract-templates.preview');
            Route::post('contract-templates/{template}/versions/{version}/restore', [ContractTemplateController::class, 'restore'])
                ->name('contract-templates.versions.restore');
            Route::post('contract-templates/{template}/make-default', [ContractTemplateController::class, 'makeDefault'])
                ->name('contract-templates.make_default');
            Route::post('contract-templates/{template}/toggle-active', [ContractTemplateController::class, 'toggleActive'])
                ->name('contract-templates.toggle_active');

        // Platform Settings (Standard Admin Access)
        Route::resource('company-profiles', CompanyProfileController::class);
        Route::resource('currencies', CurrencyController::class);
        Route::resource('contract-templates', ContractTemplateController::class)->except(['destroy'])
            ->parameters(['contract-templates' => 'template']);

        // User Management
        Route::get('users', [\App\Http\Controllers\Admin\UserAdminController::class, 'index'])->name('users.index');
        Route::post('users', [\App\Http\Controllers\Admin\UserAdminController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [\App\Http\Controllers\Admin\UserAdminController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/password', [\App\Http\Controllers\Admin\UserAdminController::class, 'password'])->name('users.password');
        Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserAdminController::class, 'destroy'])->name('users.destroy');

        // Tenant Management
        Route::resource('tenants', \App\Http\Controllers\Admin\TenantAdminController::class)->except(['show', 'destroy']);
        Route::patch('tenants/{tenant}/toggle-active', [\App\Http\Controllers\Admin\TenantAdminController::class, 'toggleActive'])->name('tenants.toggle-active');

        // Account Management (PR4D3)
        Route::resource('accounts', \App\Http\Controllers\Admin\AccountAdminController::class)->only(['index', 'show', 'update']);
        Route::patch('accounts/{account}/roles', [\App\Http\Controllers\Admin\AccountAdminController::class, 'updateRole'])->name('accounts.roles.update');
        Route::patch('accounts/{account}/owner', [\App\Http\Controllers\Admin\AccountAdminController::class, 'transferOwner'])->name('accounts.owner.update');
        
        // Dashboard (Platform Overview)
        Route::get('dashboard', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

        // Audit Logs (Global)
        Route::get('audit', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit.index');

        // Plan Requests (PR7d)
        Route::get('plan-change-requests', [\App\Http\Controllers\Admin\PlanChangeRequestAdminController::class, 'index'])->name('plan_requests.index');
        Route::get('plan-change-requests/{planChangeRequest}', [\App\Http\Controllers\Admin\PlanChangeRequestAdminController::class, 'show'])->name('plan_requests.show');
        Route::post('plan-change-requests/{planChangeRequest}/approve', [\App\Http\Controllers\Admin\PlanChangeRequestAdminController::class, 'approve'])->name('plan_requests.approve');
        Route::post('plan-change-requests/{planChangeRequest}/reject', [\App\Http\Controllers\Admin\PlanChangeRequestAdminController::class, 'reject'])->name('plan_requests.reject');
    });
});

// Local-only UI demo page & debug routes
if (app()->environment('local')) {
    Route::get('/ui', function () {
        return view('dev.ui');
    })->name('ui.index');

    Route::post('/debug/switch-tenant/{tenantId}', function ($tenantId) {
        if (!auth()->check()) {
            abort(403);
        }

        $tenant = \App\Models\Tenant::findOrFail($tenantId);
        
        // Local Debug: Membership + Active Check like Prod
        if (! auth()->user()->tenants()->where('tenants.id', $tenantId)->exists()) {
             return redirect()->back()->with('error', 'Üye değilsiniz.');
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'is_active') && !$tenant->is_active) {
            return redirect()->back()->with('error', 'Firma pasif durumda.');
        }

        session(['current_tenant_id' => $tenant->id]);

        return redirect()->back()->with('success', 'Tenant switched to ' . $tenant->name);
    })->name('debug.switch-tenant');
}

// PR3C2: Prod Switch Route
Route::middleware(['auth'])->post('/tenants/switch', function (\Illuminate\Http\Request $request) {
    $request->validate(['tenant_id' => 'required|exists:tenants,id']);
    
    $tenantId = $request->input('tenant_id');
    
    // Membership Check
    if (! auth()->user()->tenants()->where('tenants.id', $tenantId)->exists()) {
        abort(403, 'Bu firmaya erişim yetkiniz yok.');
    }

    // Active Check
    $tenant = \App\Models\Tenant::find($tenantId);
    if ($tenant && \Illuminate\Support\Facades\Schema::hasColumn('tenants', 'is_active')) {
        if (! $tenant->is_active) {
            return back()->with('error', 'Bu firma pasif durumda.');
        }
    }

    session(['current_tenant_id' => $tenantId]);

    // Domain Redirect Logic
    if (config('tenancy.resolve_by_domain') && $tenant->domain && $tenant->domain !== request()->getHost()) {
        $scheme = request()->getScheme();
        return redirect()->away($scheme . '://' . $tenant->domain . '/dashboard')
            ->with('success', 'Firma değiştirildi.');
    }

    return back()->with('success', 'Firma değiştirildi.');
})->name('tenants.switch');

// PR3C6B: Tenant Invitation Flow
Route::get('/invite/{token}', [App\Http\Controllers\TenantInvitationController::class, 'show'])->name('invitations.show');
Route::middleware(['auth'])->post('/invite/{token}/accept', [App\Http\Controllers\TenantInvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/invitations/store', [App\Http\Controllers\Admin\UserAdminController::class, 'storeInvitation'])->name('invitations.store'); // Keep existing store
    Route::resource('invitations', App\Http\Controllers\Admin\InvitationController::class)->only(['index', 'destroy']);
    Route::patch('invitations/{invitation}/regenerate', [App\Http\Controllers\Admin\InvitationController::class, 'regenerate'])->name('invitations.regenerate');
});

// PR3C8B: Tenant Admin Self-Service
Route::middleware(['auth', 'tenant.admin'])->prefix('manage')->name('manage.')->group(function () {
    // Members
    Route::get('members', [App\Http\Controllers\Manage\TenantMemberController::class, 'index'])->name('members.index');
    Route::delete('members/{user}', [App\Http\Controllers\Manage\TenantMemberController::class, 'destroy'])->name('members.destroy');
    
    // Invitations (Tenant Scoped)
    Route::get('invitations', [App\Http\Controllers\Manage\TenantInvitationAdminController::class, 'index'])->name('invitations.index');
    Route::post('invitations', [App\Http\Controllers\Manage\TenantInvitationAdminController::class, 'store'])->name('invitations.store');
    Route::patch('invitations/{invitation}/regenerate', [App\Http\Controllers\Manage\TenantInvitationAdminController::class, 'regenerate'])->name('invitations.regenerate');
    Route::delete('invitations/{invitation}', [App\Http\Controllers\Manage\TenantInvitationAdminController::class, 'destroy'])->name('invitations.destroy');
    
    // PR4C3: Support Access (Break-Glass)
    Route::post('support-access', [App\Http\Controllers\Manage\SupportAccessController::class, 'store'])->name('support-access.store');
    Route::delete('support-access/{session}', [App\Http\Controllers\Manage\SupportAccessController::class, 'destroy'])->name('support-access.destroy');
    
    // Audit Logs (Tenant Scoped)
    Route::get('audit', [App\Http\Controllers\Manage\AuditLogController::class, 'index'])->name('audit.index');

    // Billing (Owner Only) - PR4D4
    // Modified in PR7c for Plan visibility
    Route::get('plan', [\App\Http\Controllers\Manage\PlanController::class, 'index'])->name('plan.index');
    Route::get('plan/requests', [\App\Http\Controllers\Manage\PlanChangeRequestController::class, 'index'])->name('plan_requests.index');
    Route::get('plan/upgrade-request', [\App\Http\Controllers\Manage\PlanChangeRequestController::class, 'create'])->name('plan_requests.create');
    Route::post('plan/upgrade-request', [\App\Http\Controllers\Manage\PlanChangeRequestController::class, 'store'])->name('plan_requests.store');
    
    // Legacy Billing route (keep for now or redirect?)
    Route::get('billing', [App\Http\Controllers\Manage\BillingController::class, 'index'])->name('billing.index');
});

// PR4C3: Platform Support Entry
Route::middleware(['auth'])->get('/support/access/{token}', App\Http\Controllers\Admin\PlatformSupportController::class)->name('support.access');


require __DIR__ . '/auth.php';
