<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Dishtansya Food Delivery API Endpoints
|
*/

// 1. Registration API
Route::post('/register', [AuthController::class, 'register']);

// 2. Authentication / Login API
Route::post('/login', [AuthController::class, 'login']);

// 3. Authenticated Routes (Requires Bearer JWT token)
Route::middleware('jwt.auth')->group(function () {
    // Order API
    Route::post('/order', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/products', [OrderController::class, 'products']);
});
