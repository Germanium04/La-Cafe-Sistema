<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppController;

// ── AUTH (public) ──
Route::get('/login',  [AppController::class, 'showLogin'])->name('login');
Route::post('/login', [AppController::class, 'login'])->name('login.submit');
Route::post('/logout',[AppController::class, 'logout'])->name('logout');

// ── ALL AUTHENTICATED USERS (admin + staff) ──
Route::middleware('staff.auth')->group(function () {

    Route::get('/', [AppController::class, 'dashboard']);

    // Orders — staff can transact, admin can view
    Route::get('/orders',               [AppController::class, 'ordersList']);
    Route::get('/orders/{id}/receipt',  [AppController::class, 'orderReceipt']);

    // Products — read only for staff
    Route::get('/products', [AppController::class, 'products']);

    // Inventory — both can view
    Route::get('/inventory', [AppController::class, 'inventory']);
});

// ── STAFF ONLY (role: staff) ──
Route::middleware(['staff.auth', 'role:staff'])->group(function () {

    Route::get('/orders/create',        [AppController::class, 'ordersCreate']);
    Route::post('/orders',              [AppController::class, 'ordersStore']);
    Route::patch('/orders/{id}/status', [AppController::class, 'ordersUpdateStatus']);
    Route::get('/orders/{id}/edit',     [AppController::class, 'ordersEdit']);
    Route::put('/orders/{id}',          [AppController::class, 'ordersUpdate']);

    Route::post('/inventory/transaction', [AppController::class, 'inventoryTransaction']);
});

// ── ADMIN ONLY (role: admin) ──
Route::middleware(['staff.auth', 'admin.auth'])->group(function () {

    Route::get('/products/create',        [AppController::class, 'productsCreate']);
    Route::post('/products',              [AppController::class, 'productsStore']);
    Route::get('/products/{id}/edit',     [AppController::class, 'productsEdit']);
    Route::put('/products/{id}',          [AppController::class, 'productsUpdate']);
    Route::post('/products/{id}/delete',  [AppController::class, 'productsDelete']);
    Route::post('/ingredients',           [AppController::class, 'ingredientsStore']);

    // Inventory approval workflow
    Route::post('/inventory/{id}/approve', [AppController::class, 'inventoryApprove']);
    Route::post('/inventory/{id}/reject',  [AppController::class, 'inventoryReject']);

    Route::get('/reports', [AppController::class, 'reports']);
});