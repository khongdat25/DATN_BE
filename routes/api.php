<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductApi;
use App\Http\Controllers\CategoryApi;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\flashsale;

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
Route::get('/flash-sale', [flashsale::class, 'show']); /*lọc status 1 2 3 với 1 = đang chạy, 2 = sắp diễn ra/ ngưng, 3 = đã kết thúc*/
Route::delete('/flash-sale/delete/{id}', [flashsale::class, 'destroy']);
Route::post('/flash-sale/add', [flashsale::class, 'add']);
Route::put('/flash-sale/edit/{id}', [flashsale::class, 'edit']);
Route::patch('/flash-sale/toggle-cate/{id}', [flashsale::class, 'togglecate']);
Route::patch('/flash-sale/end-camp/{id}', [flashsale::class, 'endcamp']);


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
Route::get('/product/{slug}', [ProductApi::class, 'Detail']);
Route::get('/search', [ProductApi::class, 'Search']);
    
// Xác thực (Auth) công khai
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Webhook thanh toán PayOS
Route::post('/payment/payos-webhook', [CheckoutController::class, 'payosWebhook']);

// API yêu cầu xác thực JWT (Guard: api)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Địa chỉ (Address)
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    // Giỏ hàng (Cart)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);

    // Thanh toán (Checkout)
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::get('/vouchers/available', [VoucherController::class, 'getAvailableVouchers']);
    Route::post('/vouchers/apply', [VoucherController::class, 'applyVoucher']);

    // CRUD product
    Route::get('adminproduct', [ProductApi::class, 'admin_product']);
    Route::post('product_add', [ProductApi::class, 'product_add']);
    Route::post('product_edit/{id}', [ProductApi::class, 'product_edit']);
    Route::post('upload', [ProductApi::class, 'uploadImage']);
    Route::delete('variant/{v}', [ProductApi::class, 'variant_delete']);
    Route::delete('product/{id}', [ProductApi::class, 'product_delete']);

    // CRUD danh mục
    Route::get('admincategory', [CategoryApi::class, 'admin_category']);
    Route::post('category_add', [CategoryApi::class, 'add']);
    Route::post('category_edit/{id}', [CategoryApi::class, 'edit']);
    Route::patch('toggle/{id}', [CategoryApi::class, 'togglecate']);
    Route::delete('category/{category}', [CategoryApi::class, 'destroy']);

    // Đơn hàng (Orders) cho User
    Route::get('/user/orders', [OrderController::class, 'userIndex']);
    Route::get('/user/orders/{id}', [OrderController::class, 'userShow']);
    Route::delete('/user/orders/{id}', [OrderController::class, 'userDestroy']);
    Route::post('/user/orders/{id}/cancel', [OrderController::class, 'userCancel']);

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

    // Quản lý đơn hàng (Orders) cho Admin
    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::post('/orders/{id}/status', [OrderController::class, 'adminUpdateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'adminDestroy']);

    // Quản lý Vouchers (Admin)
    Route::apiResource('/vouchers', VoucherController::class);
});


