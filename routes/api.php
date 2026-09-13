<?php

use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes (tidak butuh login)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);   // tahap 1
Route::post('/reset-password', [AuthController::class, 'resetPassword']);    // tahap 2

// Katalog & search buku bisa diakses tanpa login (landing page / browse dulu)
Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/catalog/{book}', [CatalogController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Authenticated routes (wajib login, Bearer token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ---------- USER: Cart & Checkout ----------
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);

    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::get('/checkout/{orderCode}', [CheckoutController::class, 'show']);

    // ---------- Live Chat (dipakai user & admin, dibedakan role di controller) ----------
    Route::get('/chat', [ChatController::class, 'index']);
    Route::post('/chat', [ChatController::class, 'store']);

    /*
    |----------------------------------------------------------------------
    | ADMIN routes (butuh permission spesifik via RBAC)
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        Route::middleware('permission:manage-categories')->group(function () {
            Route::apiResource('categories', CategoryController::class);
        });

        Route::middleware('permission:manage-books')->group(function () {
            Route::apiResource('books', BookController::class);
        });

        Route::middleware('permission:manage-users')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::get('users/{user}', [UserController::class, 'show']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);
        });

        Route::middleware('permission:confirm-orders')->group(function () {
            Route::get('orders', [OrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::post('orders/{order}/confirm', [OrderController::class, 'confirm']);
            Route::post('orders/{order}/complete', [OrderController::class, 'complete']);
            Route::get('orders/{order}/invoice', [InvoiceController::class, 'download']);
        });

        Route::middleware('permission:view-reports')->group(function () {
            Route::get('reports', [ReportController::class, 'index']);
            Route::get('reports/download', [ReportController::class, 'download']);
        });

        Route::middleware('permission:reply-chat')->group(function () {
            Route::get('chat/conversations', [ChatController::class, 'conversations']);
        });
    });
});