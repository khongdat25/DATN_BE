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
//lấy tên brand + category
Route::get('/getbrands', [ProductApi::class, 'getBrands']);
Route::get('/getcategories', [ProductApi::class, 'getCategories']);
// sản phẩm 
Route::get('/products', [ProductApi::class, 'index']);
Route::get('/banner', [ProductApi::class, 'Banner']);
Route::get('/categories', [ProductApi::class, 'HotCategories']);
Route::get('/flashsales', [ProductApi::class, 'FlashSale']);
Route::get('/bestsellings', [ProductApi::class, 'BestSelling']);
Route::get('/hotproducts', [ProductApi::class, 'HotProduct']);
Route::get('/product/{id}', [ProductApi::class, 'Detail']);
Route::get('/search', [ProductApi::class, 'Search']);
    
// Xác thực (Auth) công khai
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);

// API yêu cầu xác thực JWT (Guard: api)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Giỏ hàng (Cart)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);

    // Thanh toán (Checkout)
    Route::post('/checkout', [CheckoutController::class, 'store']);

    //product
    Route::get('adminproduct', [ProductApi::class, 'admin_product']);
    Route::post('product_add', [ProductApi::class, 'product_add']);
    Route::post('product_edit/{id}', [ProductApi::class, 'product_edit']);
    Route::delete('variant/{v}', [ProductApi::class, 'variant_delete']);
    Route::delete('product/{id}', [ProductApi::class, 'product_delete']);

});

// API dành riêng cho Admin (Yêu cầu xác thực và quyền Admin)
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard-stats', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => 158400000,
                'orders' => 342,
                'customers' => 1250,
                'conversion_rate' => 3.2
            ]
        ]);
    });
});


