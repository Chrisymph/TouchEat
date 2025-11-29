<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth');

// Routes d'authentification client
Route::get('/client-auth', [AuthController::class, 'showClientAuth'])->name('client.auth');
Route::post('/client-login', [AuthController::class, 'clientLogin'])->name('client.login');
Route::post('/client-register', [AuthController::class, 'clientRegister'])->name('client.register');

// Routes d'authentification admin
Route::get('/admin-auth', [AuthController::class, 'showAdminAuth'])->name('admin.auth');
Route::post('/admin-login', [AuthController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin-register', [AuthController::class, 'adminRegister'])->name('admin.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Routes admin
Route::middleware(['admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/ajax', [AdminController::class, 'ordersAjax'])->name('admin.orders.ajax');
    Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
    Route::get('/orders/{id}', [AdminController::class, 'showOrder'])->name('admin.orders.show');
    Route::get('/orders/{id}/ajax', [AdminController::class, 'showOrder'])->name('admin.orders.ajax.details');
    Route::post('/orders/{id}/add-time', [AdminController::class, 'addTimeToOrder'])->name('admin.orders.add-time');
    Route::post('/orders/{id}/status-ajax', [AdminController::class, 'updateOrderStatusAjax'])->name('admin.orders.status.ajax');
    Route::get('/orders/{id}/receipt', [AdminController::class, 'generateReceipt'])->name('admin.orders.receipt');
    Route::get('/orders/{id}/print', [AdminController::class, 'printReceipt'])->name('admin.orders.print');
    
    Route::get('/menu/ajax', [AdminController::class, 'menuAjax'])->name('admin.menu.ajax');
    Route::get('/menu', [AdminController::class, 'menu'])->name('admin.menu');
    Route::post('/menu', [AdminController::class, 'addMenuItem'])->name('admin.menu.add');
    Route::put('/menu/{id}', [AdminController::class, 'updateMenuItem'])->name('admin.menu.update');
    Route::delete('/menu/{id}', [AdminController::class, 'deleteMenuItem'])->name('admin.menu.delete');
    Route::post('/menu/{id}/toggle', [AdminController::class, 'toggleMenuItemAvailability'])->name('admin.menu.toggle');
    Route::post('/menu/{id}/promotion', [AdminController::class, 'addPromotion'])->name('admin.menu.promotion.add');
    Route::delete('/menu/{id}/promotion', [AdminController::class, 'removePromotion'])->name('admin.menu.promotion.remove');
    Route::get('/menu/{id}/ajax', [AdminController::class, 'getMenuItem'])->name('admin.menu.item.ajax');

    // Routes pour la gestion des clients
    Route::get('/clients/ajax', [AdminController::class, 'clientsAjax'])->name('admin.clients.ajax');
    Route::get('/clients/available', [AdminController::class, 'getAvailableClients'])->name('admin.clients.available');
    Route::post('/clients/link', [AdminController::class, 'linkClients'])->name('admin.clients.link');
    Route::delete('/clients/{clientId}/unlink', [AdminController::class, 'unlinkClient'])->name('admin.clients.unlink');
    Route::post('/clients/{clientId}/suspend', [AdminController::class, 'suspendClient'])->name('admin.clients.suspend');
    Route::post('/clients/{clientId}/activate', [AdminController::class, 'activateClient'])->name('admin.clients.activate');
    
    // Routes pour les rapports
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/reports/ajax', [AdminController::class, 'reportsAjax'])->name('admin.reports.ajax');
    Route::get('/reports/chart-data', [AdminController::class, 'reportsChartData'])->name('admin.reports.chart');
    Route::post('/reports/save', [AdminController::class, 'saveReport'])->name('admin.reports.save');
    Route::post('/reports/generate-date-report', [AdminController::class, 'generateDateReport'])->name('admin.reports.generate-date');
    Route::post('/reports/download-date-report', [AdminController::class, 'downloadDateReport'])->name('admin.reports.download-date');
    
    // Routes pour les transactions SMS
    Route::get('/sms-transactions', [AdminController::class, 'smsTransactions'])->name('admin.sms.transactions');
    Route::get('/sms-debug', [AdminController::class, 'smsDebug'])->name('admin.sms.debug');
    
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
});

// Routes client
Route::middleware(['auth'])->prefix('client')->group(function () {
    Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('client.dashboard');
    Route::post('/cart/add', [ClientController::class, 'addToCart'])->name('client.cart.add');
    Route::post('/cart/update', [ClientController::class, 'updateCart'])->name('client.cart.update');
    Route::post('/order/place', [ClientController::class, 'placeOrder'])->name('client.order.place');
    Route::get('/order/{id}/status', [ClientController::class, 'getOrderStatus'])->name('client.order.status');
    Route::get('/order/{id}/confirmation', [ClientController::class, 'orderConfirmation'])->name('client.order.confirmation');
    Route::post('/order/{orderId}/add-item', [ClientController::class, 'addToExistingOrder'])->name('client.order.add-item');
    
    // Routes pour le paiement USSD
    Route::get('/order/{id}/ussd', [ClientController::class, 'showUssdCommand'])->name('client.order.ussd');
    
    // Routes pour le paiement avec vérification SMS
    Route::get('/order/{id}/transaction', [ClientController::class, 'showTransactionForm'])->name('client.payment.form');
    Route::post('/order/{id}/process-payment', [ClientController::class, 'processTransaction'])->name('client.payment.process');
    
    Route::post('/order/{orderId}/request-delivery', [ClientController::class, 'requestDelivery'])->name('client.order.request-delivery');
    Route::get('/order-history', [ClientController::class, 'orderHistory'])->name('client.order.history');

    // Routes pour la synchronisation SMS
    Route::post('/force-sync-sms', [ClientController::class, 'forceSyncSMS'])->name('client.sms.force-sync');

    Route::post('/logout', [AuthController::class, 'logout'])->name('client.logout');
});

// Routes SMS (accessibles sans auth pour PC Sync)
Route::post('/client/sync-pcsync', [ClientController::class, 'syncWithPCSync']);
Route::post('/client/sms-webhook', [ClientController::class, 'receiveSMSWebhook']);
Route::post('/client/sync-mobiletrans', [ClientController::class, 'syncWithMobileTransFiles']);