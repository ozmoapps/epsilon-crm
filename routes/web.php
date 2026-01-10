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

    Route::resource('customers', CustomerController::class);
    Route::resource('vessels', VesselController::class);
    Route::resource('work-orders', WorkOrderController::class);
    Route::get('work-orders/{workOrder}/print', [WorkOrderController::class, 'printView'])->name('work-orders.print');
    Route::resource('quotes', QuoteController::class);
    Route::get('quotes/{quote}/preview', [QuoteController::class, 'preview'])->name('quotes.preview');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('quotes/{quote}/print', [QuoteController::class, 'printView'])->name('quotes.print');
    Route::resource('contracts', ContractController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('sales-orders', SalesOrderController::class);
    Route::get('sales-orders/{salesOrder}/contracts/create', [ContractController::class, 'create'])
        ->name('sales-orders.contracts.create');
    Route::post('sales-orders/{salesOrder}/contracts', [ContractController::class, 'store'])
        ->name('sales-orders.contracts.store');
    Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
    Route::get('contracts/{contract}/print', [ContractController::class, 'printView'])->name('contracts.print');
    Route::post('contracts/{contract}/attachments', [ContractAttachmentController::class, 'store'])
        ->name('contracts.attachments.store');
    Route::get('contracts/{contract}/attachments/{attachment}', [ContractAttachmentController::class, 'download'])
        ->name('contracts.attachments.download');
    Route::delete('contracts/{contract}/attachments/{attachment}', [ContractAttachmentController::class, 'destroy'])
        ->name('contracts.attachments.destroy');
    Route::get('contracts/{contract}/delivery-pack', [ContractDeliveryController::class, 'downloadPack'])
        ->name('contracts.delivery_pack');
    Route::post('contracts/{contract}/deliveries', [ContractDeliveryController::class, 'store'])
        ->name('contracts.deliveries.store');
    Route::patch('contracts/{contract}/deliveries/{delivery}/mark-sent', [ContractDeliveryController::class, 'markSent'])
        ->name('contracts.deliveries.mark_sent');
    Route::post('contracts/{contract}/revise', [ContractController::class, 'revise'])
        ->name('contracts.revise');
    Route::patch('contracts/{contract}/mark-sent', [ContractController::class, 'markSent'])
        ->name('contracts.mark_sent');
    Route::patch('contracts/{contract}/mark-signed', [ContractController::class, 'markSigned'])
        ->name('contracts.mark_signed');
    Route::patch('contracts/{contract}/cancel', [ContractController::class, 'cancel'])
        ->name('contracts.cancel');
    Route::patch('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
        ->name('sales-orders.confirm');
    Route::patch('sales-orders/{salesOrder}/start', [SalesOrderController::class, 'start'])
        ->name('sales-orders.start');
    Route::patch('sales-orders/{salesOrder}/complete', [SalesOrderController::class, 'complete'])
        ->name('sales-orders.complete');
    Route::patch('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])
        ->name('sales-orders.cancel');
    Route::post('quotes/{quote}/mark-sent', [QuoteController::class, 'markAsSent'])
        ->name('quotes.mark_sent');
    Route::post('quotes/{quote}/mark-accepted', [QuoteController::class, 'markAsAccepted'])
        ->name('quotes.mark_accepted');
    Route::post('quotes/{quote}/convert-to-sales-order', [QuoteController::class, 'convertToSalesOrder'])
        ->name('quotes.convert_to_sales_order');
    Route::post('quotes/{quote}/items', [QuoteItemController::class, 'store'])->name('quotes.items.store');
    Route::put('quotes/{quote}/items/{item}', [QuoteItemController::class, 'update'])->name('quotes.items.update');
    Route::delete('quotes/{quote}/items/{item}', [QuoteItemController::class, 'destroy'])->name('quotes.items.destroy');
    Route::post('sales-orders/{salesOrder}/items', [SalesOrderItemController::class, 'store'])->name('sales-orders.items.store');
    Route::put('sales-orders/{salesOrder}/items/{item}', [SalesOrderItemController::class, 'update'])->name('sales-orders.items.update');
    Route::delete('sales-orders/{salesOrder}/items/{item}', [SalesOrderItemController::class, 'destroy'])->name('sales-orders.items.destroy');

    Route::post('/follow-ups', [App\Http\Controllers\FollowUpController::class, 'store'])->name('follow-ups.store');
    Route::post('/follow-ups/{followUp}/complete', [App\Http\Controllers\FollowUpController::class, 'complete'])->name('follow-ups.complete');
    Route::delete('/follow-ups/{followUp}', [App\Http\Controllers\FollowUpController::class, 'destroy'])->name('follow-ups.destroy');

    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        // Settings
        Route::resource('company-profiles', CompanyProfileController::class);
        Route::resource('bank-accounts', BankAccountController::class);
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

require __DIR__ . '/auth.php';
