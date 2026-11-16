<?php

use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PaymentConditionController as AdminPaymentConditionController;
use App\Http\Controllers\Api\Admin\PriceTableController as AdminPriceTableController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentConditionController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok', 'time' => now()->toIso8601String()]);

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/catalog/featured', [ProductController::class, 'featured']);
Route::get('/catalog/filters', [ProductController::class, 'filters']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product');
Route::get('/payment-conditions', [PaymentConditionController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/cart/preview', [CartController::class, 'preview']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'stats']);

    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::put('/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

    Route::get('/customers', [AdminCustomerController::class, 'index']);
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->whereNumber('customer');
    Route::post('/customers', [AdminCustomerController::class, 'store']);
    Route::put('/customers/{customer}', [AdminCustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [AdminCustomerController::class, 'destroy']);

    Route::get('/promotions', [AdminPromotionController::class, 'index']);
    Route::post('/promotions', [AdminPromotionController::class, 'store']);
    Route::put('/promotions/{promotion}', [AdminPromotionController::class, 'update']);
    Route::delete('/promotions/{promotion}', [AdminPromotionController::class, 'destroy']);

    Route::get('/payment-conditions', [AdminPaymentConditionController::class, 'index']);
    Route::post('/payment-conditions', [AdminPaymentConditionController::class, 'store']);
    Route::put('/payment-conditions/{paymentCondition}', [AdminPaymentConditionController::class, 'update']);
    Route::delete('/payment-conditions/{paymentCondition}', [AdminPaymentConditionController::class, 'destroy']);

    Route::get('/price-tables', [AdminPriceTableController::class, 'index']);
    Route::get('/price-tables/{priceTable}/items', [AdminPriceTableController::class, 'items']);
    Route::put('/price-tables/{priceTable}/items/{product}', [AdminPriceTableController::class, 'updateItem']);

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
});
