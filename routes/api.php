<?php

use App\Http\Controllers\AIChatController;
use App\Http\Controllers\AdminAIController;
use App\Http\Controllers\GHNController;
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
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReceiptController;
use App\Models\Order;
use App\Models\Product;
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
*/ /*test */

// route::post('/update-stock', [ReceiptController::class, 'updateStock']);
// route::get('/stock-log/{id}', [ReceiptController::class, 'checkReceipts']);

// lấy tên brand + category
Route::get('/getbrands', [ProductController::class, 'getBrands']);
Route::get('/getcategories', [ProductController::class, 'getCategories']);
// sản phẩm
Route::get('/products', [ProductController::class, 'index']);
Route::get('/banner', [BannerController::class, 'publicIndex']);
Route::get('/categories', [ProductController::class, 'HotCategories']);
Route::get('/flashsales', [ProductController::class, 'FlashSale']);
Route::get('/bestsellings', [ProductController::class, 'BestSelling']);
Route::get('/hotproducts', [ProductController::class, 'HotProduct']);
Route::get('/product/{slug}', [ProductController::class, 'Detail']);
Route::get('/search', [ProductController::class, 'Search']);
Route::post('/ai/chat', [AIChatController::class, 'chat']);

// GHN Express API
Route::get('/ghn/provinces', [GHNController::class, 'getProvinces']);
Route::get('/ghn/districts', [GHNController::class, 'getDistricts']);
Route::get('/ghn/wards', [GHNController::class, 'getWards']);
Route::post('/ghn/calculate-fee', [GHNController::class, 'calculateFee']);
Route::get('/ghn/tracking/{code}', [GHNController::class, 'trackGHNOrder']);
Route::get('/ghn/track/{code}', [GHNController::class, 'trackGHNOrder']);

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


    // Yêu thích (Wishlist / Favorite)
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::delete('/wishlist/clear', [WishlistController::class, 'clear']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

    // Thông báo (Notifications)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Đơn hàng (Orders) cho User
    Route::get('/user/orders', [OrderController::class, 'userIndex']);
    Route::get('/user/orders/{id}', [OrderController::class, 'userShow']);
    Route::delete('/user/orders/{id}', [OrderController::class, 'userDestroy']);
    Route::post('/user/orders/{id}/cancel', [OrderController::class, 'userCancel']);
    Route::post('/user/orders/{id}/confirm-payment', [OrderController::class, 'userConfirmPayment']);

    // Đánh giá sản phẩm (Rating)
    Route::post('/ratings', [RatingController::class, 'store']);

    // Quản lý Sản phẩm (Admin Products)
    Route::get('/adminproduct', [ProductController::class, 'admin_product']);
    Route::post('/product_add', [ProductController::class, 'product_add']);
    Route::post('/product_edit/{id}', [ProductController::class, 'product_edit']);
    Route::post('/product_import_excel', [ProductController::class, 'importExcel']);
    Route::patch('/product/toggle-featured/{id}', [ProductController::class, 'toggleFeatured']);
    Route::patch('/product/toggle-status/{id}', [ProductController::class, 'toggleStatus']);
    Route::patch('/product/toggle-cate/{id}', [ProductController::class, 'toggleStatus']);
    Route::delete('/product/{id}', [ProductController::class, 'product_delete']);
    Route::delete('/variant/{v}', [ProductController::class, 'variant_delete']);
    Route::post('/upload', [ProductController::class, 'uploadImage']);

    // Quản lý Size (Admin & Management)
    Route::get('/size', [SizeController::class, 'index']);
    Route::post('/size/add', [SizeController::class, 'add']);
    Route::put('/size/edit/{id}', [SizeController::class, 'edit']);
    Route::patch('/size/toggle-cate/{id}', [SizeController::class, 'togglecate']);
    Route::delete('/size/delete/{size}', [SizeController::class, 'destroy']);

    // Quản lý Màu sắc (Admin & Management)
    Route::get('/color', [ColorController::class, 'index']);
    Route::post('/color/add', [ColorController::class, 'add']);
    Route::put('/color/edit/{id}', [ColorController::class, 'edit']);
    Route::patch('/color/toggle-cate/{id}', [ColorController::class, 'togglecate']);
    Route::delete('/color/delete/{color}', [ColorController::class, 'destroy']);

    // Quản lý Thương hiệu (Admin & Management)
    Route::get('/brand', [BrandController::class, 'index']);
    Route::post('/brand/add', [BrandController::class, 'add']);
    Route::put('/brand/edit/{id}', [BrandController::class, 'edit']);
    Route::patch('/brand/toggle-cate/{id}', [BrandController::class, 'togglecate']);
    Route::delete('/brand/delete/{brand}', [BrandController::class, 'destroy']);

    // Quản lý Danh mục (Admin & Management)
    Route::get('/admincategory', [CategoryApi::class, 'admin_category']);
    Route::post('/category_add', [CategoryApi::class, 'add']);
    Route::post('/category_edit/{id}', [CategoryApi::class, 'edit']);
    Route::delete('/category/{category}', [CategoryApi::class, 'destroy']);
    Route::patch('/toggle/{id}', [CategoryApi::class, 'togglecate']);

    // Quản lý Flash Sale (Admin & Management)
    Route::get('/flash-sale', [flashsale::class, 'show']);
    Route::delete('/flash-sale/delete/{id}', [flashsale::class, 'destroy']);
    Route::post('/flash-sale/add', [flashsale::class, 'add']);
    Route::put('/flash-sale/edit/{id}', [flashsale::class, 'edit']);
    Route::patch('/flash-sale/toggle-cate/{id}', [flashsale::class, 'togglecate']);
    Route::patch('/flash-sale/end-camp/{id}', [flashsale::class, 'endcamp']);
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
    Route::post('/orders/{id}/push-ghn', [GHNController::class, 'pushOrderToGHN']);
    Route::post('/orders/{id}/cancel-ghn', [GHNController::class, 'cancelGHNOrder']);
    Route::delete('/orders/{id}', [OrderController::class, 'adminDestroy']);

    // Quản lý Vouchers (Admin)
    Route::apiResource('/vouchers', VoucherController::class);

    // Quản lý Người dùng (Users Management for Admin)
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Quản lý Đánh giá (Admin Reviews)
    Route::get('/reviews', [RatingController::class, 'adminIndex']);
    Route::post('/reviews/{id}/reply', [RatingController::class, 'adminReply']);
    Route::put('/reviews/{id}/status', [RatingController::class, 'adminUpdateStatus']);
    Route::delete('/reviews/{id}', [RatingController::class, 'adminDestroy']);

    // Quản lý Banner (Admin Banners)
    Route::get('/banners', [BannerController::class, 'adminIndex']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::put('/banners/{id}', [BannerController::class, 'update']);
    Route::patch('/banners/{id}/toggle', [BannerController::class, 'toggleStatus']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);

    // Quản lý Bộ sưu tập (Admin Collections)
    Route::get('/collections', [\App\Http\Controllers\CollectionController::class, 'adminIndex']);
    Route::post('/collections', [\App\Http\Controllers\CollectionController::class, 'store']);
    Route::post('/collections/{id}', [\App\Http\Controllers\CollectionController::class, 'update']);
    Route::patch('/collections/{id}/toggle', [\App\Http\Controllers\CollectionController::class, 'toggleStatus']);
    Route::patch('/collections/{id}/toggle-featured', [\App\Http\Controllers\CollectionController::class, 'toggleFeatured']);
    Route::delete('/collections/{id}', [\App\Http\Controllers\CollectionController::class, 'destroy']);

    // Quản lý AI Assistant (Admin AI Management)
    Route::get('/ai/stats', [AdminAIController::class, 'getStats']);
    Route::get('/ai/settings', [AdminAIController::class, 'getSettings']);
    Route::post('/ai/settings', [AdminAIController::class, 'updateSettings']);
    Route::get('/ai/logs', [AdminAIController::class, 'getLogs']);
    Route::get('/ai/suggestions', [AdminAIController::class, 'getSuggestions']);
    Route::post('/ai/suggestions', [AdminAIController::class, 'updateSuggestions']);
});

// Bộ sưu tập công khai (Public Collections)
Route::get('/collections', [\App\Http\Controllers\CollectionController::class, 'publicIndex']);
Route::get('/collections/{slugOrId}', [\App\Http\Controllers\CollectionController::class, 'publicShow']);



