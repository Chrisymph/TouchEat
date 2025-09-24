<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes d'authentification client
Route::get('/client-auth', [AuthController::class, 'showClientAuth'])->name('client.auth');
Route::post('/client-login', [AuthController::class, 'clientLogin'])->name('client.login');
Route::post('/client-register', [AuthController::class, 'clientRegister'])->name('client.register');

// Routes d'authentification admin
Route::get('/admin-auth', [AuthController::class, 'showAdminAuth'])->name('admin.auth');
Route::post('/admin-login', [AuthController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin-register', [AuthController::class, 'adminRegister'])->name('admin.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Routes admin - CORRECTION : utiliser 'admin' au lieu de 'auth'
Route::middleware(['admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
    Route::get('/orders/{id}', [AdminController::class, 'showOrder'])->name('admin.orders.show');
    
    Route::get('/menu', [AdminController::class, 'menu'])->name('admin.menu');
    Route::post('/menu', [AdminController::class, 'addMenuItem'])->name('admin.menu.add');
    Route::put('/menu/{id}', [AdminController::class, 'updateMenuItem'])->name('admin.menu.update');
    Route::delete('/menu/{id}', [AdminController::class, 'deleteMenuItem'])->name('admin.menu.delete');
    Route::post('/menu/{id}/toggle', [AdminController::class, 'toggleMenuItemAvailability'])->name('admin.menu.toggle');
    Route::post('/menu/{id}/promotion', [AdminController::class, 'addPromotion'])->name('admin.menu.promotion.add');
    Route::delete('/menu/{id}/promotion', [AdminController::class, 'removePromotion'])->name('admin.menu.promotion.remove');
    
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
});

// Routes client (si vous en avez)
Route::middleware(['auth'])->group(function () {
    // Routes pour les clients...
});