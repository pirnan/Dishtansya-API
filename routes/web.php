<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'Dishtansya Food Delivery API',
        'status' => 'operational',
        'version' => '1.0.0',
        'docs' => '/api-docs',
    ]);
});

// Also accept direct root requests as described in examination scenario outlines
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/order', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/products', [OrderController::class, 'products']);
});
