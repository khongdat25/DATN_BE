<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductApi;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/products', [ProductApi::class, 'index']);
Route::get('/banner', [ProductApi::class, 'Banner']);
Route::get('/categories', [ProductApi::class, 'HotCategories']);
Route::get('/flashsales', [ProductApi::class, 'Flashsale']);
Route::get('/bestsellings', [ProductApi::class, 'Bestselling']);
Route::get('/hotproducts', [ProductApi::class, 'Hotproduct']);
Route::get('/product/{id}', [ProductApi::class, 'detail']);

Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class,
])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::put('/cart/{id}', [CartController::class, 'update']);
        Route::delete('/cart/{id}', [CartController::class, 'destroy']);

        Route::post('/checkout', [CheckoutController::class, 'store']);
    });
});

