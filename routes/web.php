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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/saved-views', [App\Http\Controllers\SavedViewController::class, 'index'])->name('saved-views.index');
    Route::post('/saved-views', [App\Http\Controllers\SavedViewController::class, 'store'])->name('saved-views.store');
    Route::delete('/saved-views/{savedView}', [App\Http\Controllers\SavedViewController::class, 'destroy'])->name('saved-views.destroy');

    Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDestroy'])->name('customers.bulk_destroy');

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
    
    // Financial & Sensitive Routes (Admin Only)
    Route::middleware(['admin'])->group(function () {

        // Ledger Routes
        Route::get('customers/{customer}/ledger', [\App\Http\Controllers\CustomerLedgerController::class, 'index'])->name('customers.ledger');
        Route::get('customers/{customer}/ledger/manual/create', [\App\Http\Controllers\CustomerLedgerManualEntryController::class, 'create'])->name('customers.ledger.manual.create');
        Route::post('customers/{customer}/ledger/manual', [\App\Http\Controllers\CustomerLedgerManualEntryController::class, 'store'])->name('customers.ledger.manual.store');
        Route::delete('customers/{customer}/ledger/manual/{entry}', [\App\Http\Controllers\CustomerLedgerManualEntryController::class, 'destroy'])->name('customers.ledger.manual.destroy');
        
        // Global Customer Ledger Index
        Route::get('/customer-ledgers', [\App\Http\Controllers\CustomerLedgerIndexController::class, 'index'])->name('customer-ledgers.index');

        // Quotes
        Route::post('quotes/bulk-delete', [QuoteController::class, 'bulkDestroy'])->name('quotes.bulk_destroy');
        Route::resource('quotes', QuoteController::class);
        Route::get('quotes/{quote}/preview', [QuoteController::class, 'preview'])->name('quotes.preview');
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
        Route::get('quotes/{quote}/print', [QuoteController::class, 'printView'])->name('quotes.print');
        Route::post('quotes/{quote}/mark-sent', [QuoteController::class, 'markAsSent'])->name('quotes.mark_sent');
        Route::post('quotes/{quote}/mark-accepted', [QuoteController::class, 'markAsAccepted'])->name('quotes.mark_accepted');
        Route::post('quotes/{quote}/convert-to-sales-order', [QuoteController::class, 'convertToSalesOrder'])->name('quotes.convert_to_sales_order');
        Route::post('quotes/{quote}/items', [QuoteItemController::class, 'store'])->name('quotes.items.store');
        Route::put('quotes/{quote}/items/{item}', [QuoteItemController::class, 'update'])->name('quotes.items.update');
        Route::delete('quotes/{quote}/items/{item}', [QuoteItemController::class, 'destroy'])->name('quotes.items.destroy');

        // Contracts
        Route::resource('contracts', ContractController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
        Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
        Route::get('contracts/{contract}/print', [ContractController::class, 'printView'])->name('contracts.print');
        Route::post('contracts/{contract}/attachments', [ContractAttachmentController::class, 'store'])->name('contracts.attachments.store');
        Route::get('contracts/{contract}/attachments/{attachment}', [ContractAttachmentController::class, 'download'])->name('contracts.attachments.download');
        Route::delete('contracts/{contract}/attachments/{attachment}', [ContractAttachmentController::class, 'destroy'])->name('contracts.attachments.destroy');
        Route::get('contracts/{contract}/delivery-pack', [ContractDeliveryController::class, 'downloadPack'])->name('contracts.delivery_pack');
        Route::post('contracts/{contract}/deliveries', [ContractDeliveryController::class, 'store'])->name('contracts.deliveries.store');
        Route::patch('contracts/{contract}/deliveries/{delivery}/mark-sent', [ContractDeliveryController::class, 'markSent'])->name('contracts.deliveries.mark_sent');
        Route::post('contracts/{contract}/revise', [ContractController::class, 'revise'])->name('contracts.revise');
        Route::patch('contracts/{contract}/mark-sent', [ContractController::class, 'markSent'])->name('contracts.mark_sent');
        Route::patch('contracts/{contract}/mark-signed', [ContractController::class, 'markSigned'])->name('contracts.mark_signed');
        Route::patch('contracts/{contract}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');

        // Sales Orders
        Route::resource('sales-orders', SalesOrderController::class);
        Route::get('sales-orders/{salesOrder}/contracts/create', [ContractController::class, 'create'])->name('sales-orders.contracts.create');
        Route::post('sales-orders/{salesOrder}/contracts', [ContractController::class, 'store'])->name('sales-orders.contracts.store');
        Route::patch('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
        Route::patch('sales-orders/{salesOrder}/start', [SalesOrderController::class, 'start'])->name('sales-orders.start');
        Route::patch('sales-orders/{salesOrder}/complete', [SalesOrderController::class, 'complete'])->name('sales-orders.complete');
        Route::patch('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
        Route::post('sales-orders/{salesOrder}/items', [SalesOrderItemController::class, 'store'])->name('sales-orders.items.store');
        Route::put('sales-orders/{salesOrder}/items/{item}', [SalesOrderItemController::class, 'update'])->name('sales-orders.items.update');
        Route::delete('sales-orders/{salesOrder}/items/{item}', [SalesOrderItemController::class, 'destroy'])->name('sales-orders.items.destroy');
        Route::post('sales-orders/{salesOrder}/post-stock', [SalesOrderController::class, 'postStock'])->name('sales-orders.post-stock');
        Route::post('sales-orders/{salesOrder}/create-work-order', [SalesOrderController::class, 'createWorkOrder'])->name('sales-orders.create-work-order');

        // Shipments & Returns (linked to Sales Orders)
        Route::get('sales-orders/{salesOrder}/shipments/create', [App\Http\Controllers\SalesOrderShipmentController::class, 'create'])->name('sales-orders.shipments.create');
        Route::post('sales-orders/{salesOrder}/shipments', [App\Http\Controllers\SalesOrderShipmentController::class, 'store'])->name('sales-orders.shipments.store');
        Route::get('sales-orders/{salesOrder}/shipments/{shipment}', [App\Http\Controllers\SalesOrderShipmentController::class, 'show'])->name('sales-orders.shipments.show');
        Route::post('shipments/{shipment}/post', [App\Http\Controllers\SalesOrderShipmentController::class, 'post'])->name('shipments.post');
        Route::delete('shipments/{shipment}', [App\Http\Controllers\SalesOrderShipmentController::class, 'destroy'])->name('shipments.destroy');
        
        Route::get('shipments/{shipment}/returns/create', [App\Http\Controllers\SalesOrderReturnController::class, 'create'])->name('shipments.returns.create');
        Route::post('shipments/{shipment}/returns', [App\Http\Controllers\SalesOrderReturnController::class, 'store'])->name('shipments.returns.store');
        Route::get('returns/{return}', [App\Http\Controllers\SalesOrderReturnController::class, 'show'])->name('returns.show');
        Route::post('returns/{return}/post', [App\Http\Controllers\SalesOrderReturnController::class, 'post'])->name('returns.post');
        Route::delete('returns/{return}', [App\Http\Controllers\SalesOrderReturnController::class, 'destroy'])->name('returns.destroy');

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
    
    // Sprint 3.2: Manual Allocation Removed (Parachute Mode Active)
    // Route::get('payments/{payment}/allocate', [App\Http\Controllers\PaymentController::class, 'allocate'])->name('payments.allocate');
    // Route::post('payments/{payment}/allocations', [App\Http\Controllers\PaymentAllocationController::class, 'store'])->name('payments.allocations.store');
    // Route::post('payments/{payment}/allocations/bulk', [App\Http\Controllers\PaymentAllocationController::class, 'storeBulk'])->name('payments.allocations.bulk');
    // Route::delete('payments/{payment}/allocations/{allocation}', [App\Http\Controllers\PaymentAllocationController::class, 'destroy'])->name('payments.allocations.destroy');

    // Admin Routes
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        // Settings
        Route::resource('company-profiles', CompanyProfileController::class);

        // Route::resource('bank-accounts', BankAccountController::class); // Moved to main group
        Route::resource('currencies', CurrencyController::class);

        Route::resource('contract-templates', ContractTemplateController::class)->except(['destroy'])
            ->parameters(['contract-templates' => 'template']);
        Route::match(['POST', 'PUT'], 'contract-templates/preview', [ContractTemplateController::class, 'preview'])
            ->name('contract-templates.preview');
        Route::post('contract-templates/{template}/versions/{version}/restore', [ContractTemplateController::class, 'restore'])
            ->name('contract-templates.versions.restore');
        Route::post('contract-templates/{template}/make-default', [ContractTemplateController::class, 'makeDefault'])
            ->name('contract-templates.make_default');
        Route::post('contract-templates/{template}/toggle-active', [ContractTemplateController::class, 'toggleActive'])
            ->name('contract-templates.toggle_active');

        // User Management
        Route::get('users', [\App\Http\Controllers\Admin\UserAdminController::class, 'index'])->name('users.index');
        Route::post('users', [\App\Http\Controllers\Admin\UserAdminController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [\App\Http\Controllers\Admin\UserAdminController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/password', [\App\Http\Controllers\Admin\UserAdminController::class, 'password'])->name('users.password');
        Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserAdminController::class, 'destroy'])->name('users.destroy');
    });
});

// Local-only UI demo page (no auth required)
if (app()->environment('local')) {
    Route::get('/ui', function () {
        return view('dev.ui');
    })->name('ui.index');
}

require __DIR__ . '/auth.php';
