<?php

use App\Http\Controllers\CustomerController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('customers', CustomerController::class);
    Route::resource('vessels', VesselController::class);
    Route::resource('work-orders', WorkOrderController::class);
    Route::resource('quotes', QuoteController::class);
    Route::resource('sales-orders', SalesOrderController::class)->only(['index', 'show', 'update']);
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
        ->name('sales-orders.confirm');
    Route::post('sales-orders/{salesOrder}/start', [SalesOrderController::class, 'start'])
        ->name('sales-orders.start');
    Route::post('sales-orders/{salesOrder}/complete', [SalesOrderController::class, 'complete'])
        ->name('sales-orders.complete');
    Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])
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
});

require __DIR__ . '/auth.php';
