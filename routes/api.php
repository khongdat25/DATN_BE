<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryApi;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\flashsale;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductApi;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\VoucherController;
use App\Models\Order;
use App\Models\ProductModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/ /*crud flashsale cần sửa */
Route::get('/flash-sale', [flashsale::class, 'show']); /*lọc status 1 2 3 với 1 = đang chạy, 2 = sắp diễn ra/ ngưng, 3 = đã kết thúc*/
Route::delete('/flash-sale/delete/{id}', [flashsale::class, 'destroy']);
Route::post('/flash-sale/add', [flashsale::class, 'add']);
Route::put('/flash-sale/edit/{id}', [flashsale::class, 'edit']);
Route::patch('/flash-sale/toggle-cate/{id}', [flashsale::class, 'togglecate']);
Route::patch('/flash-sale/end-camp/{id}', [flashsale::class, 'endcamp']);
/*crud size, color,  */
Route:: get('/size', [SizeController::class, 'index']);
Route::post('/size/add', [SizeController::class, 'add']);
Route::put('/size/edit/{id}', [SizeController::class, 'edit']);
Route::patch('/size/toggle-cate/{id}', [SizeController::class, 'togglecate']);
Route::delete('/size/delete/{size}', [SizeController::class, 'destroy']);

/*crud color */
Route:: get('/color', [ColorController::class, 'index']);
Route::post('/color/add', [ColorController::class, 'add']);
Route::put('/color/edit/{id}', [ColorController::class, 'edit']);
Route::patch('/color/toggle-cate/{id}', [ColorController::class, 'togglecate']);
Route::delete('/color/delete/{color}', [ColorController::class, 'destroy']);

//crud brand
Route:: get('/brand', [BrandController::class, 'index']);
Route::post('/brand/add', [BrandController::class, 'add']);
Route::put('/brand/edit/{id}', [BrandController::class, 'edit']);
Route::patch('/brand/toggle-cate/{id}', [BrandController::class, 'togglecate']);
Route::delete('/brand/delete/{brand}', [BrandController::class, 'destroy']);

// lấy tên brand + category
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

// Tin tức (Blogs)
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{slugOrId}', [BlogController::class, 'show']);

// Liên hệ (Contacts)
Route::post('/contacts', [ContactController::class, 'store']);

// Xác thực (Auth) công khai
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
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



    // Đơn hàng (Orders) cho User
    Route::get('/user/orders', [OrderController::class, 'userIndex']);
    Route::get('/user/orders/{id}', [OrderController::class, 'userShow']);
    Route::delete('/user/orders/{id}', [OrderController::class, 'userDestroy']);
    Route::post('/user/orders/{id}/cancel', [OrderController::class, 'userCancel']);

    

});

// API dành riêng cho Admin (Yêu cầu xác thực và quyền Admin)
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard-stats', [DashboardController::class, 'stats']);

    // Quản lý Tin tức (Blogs)
    Route::get('/blogs', [BlogController::class, 'adminIndex']);
    Route::post('/blogs', [BlogController::class, 'store']);
    Route::post('/blogs/{id}', [BlogController::class, 'update']);
    Route::delete('/blogs/{id}', [BlogController::class, 'destroy']);

    // Quản lý Liên hệ (Contacts)
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::put('/contacts/{id}', [ContactController::class, 'update']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);
    // Quản lý đơn hàng (Orders) cho Admin
    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::post('/orders/{id}/status', [OrderController::class, 'adminUpdateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'adminDestroy']);

    // Quản lý Vouchers (Admin)
    Route::apiResource('/vouchers', VoucherController::class);
});


