<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;
use App\Models\ProductModel;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductApi;
use App\Http\Controllers\CategoryApi;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\flashsale;
use App\Http\Controllers\BannerController;

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

// Tin tức (Blogs)
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{slugOrId}', [BlogController::class, 'show']);

// Banners (Public)
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/banners/{id}', [BannerController::class, 'show']);

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
        $revenue = Order::whereNotIn('status', ['cancelled', 'đã hủy', 'huy'])->sum('total_amount');
        $ordersCount = Order::count();
        $customersCount = User::where('role', 'user')->count();
        $productsCount = ProductModel::count();

        // Best sellers
        $bestSellersRaw = DB::table('order_item')
            ->join('product_variants', 'order_item.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.images',
                DB::raw('SUM(order_item.quantity) as sales'),
                DB::raw('SUM(order_item.quantity * order_item.price) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.images')
            ->orderByDesc('sales')
            ->take(3)
            ->get();

        $bestSellers = $bestSellersRaw->map(function ($item) {
            $images = json_decode($item->images, true);
            $image = (is_array($images) && count($images) > 0) ? $images[0] : '/images/placeholder.png';
            if (!str_starts_with($image, 'http') && !str_starts_with($image, '/')) {
                $image = '/' . $image;
            }
            if (!str_starts_with($image, 'http') && !str_starts_with($image, '/images') && !str_starts_with($image, 'images')) {
                $image = '/images/' . ltrim($image, '/');
            }
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sales' => (int)$item->sales,
                'revenue' => (float)$item->revenue,
                'image' => url($image),
                'trendingUp' => true,
                'change' => rand(5, 20) . '%'
            ];
        });

        // Recent orders
        $recentOrders = Order::orderBy('created_at', 'desc')->take(5)->get()->map(function ($order) {
            $statusStr = strtolower((string)$order->status);
            $statusText = 'Chờ xử lý';
            $statusClass = 'bg-amber-50 text-amber-700';
            $bulletClass = 'bg-amber-500';

            if (in_array($statusStr, ['đang giao', 'shipping', 'shipped'])) {
                $statusText = 'Đang giao';
                $statusClass = 'bg-blue-50 text-blue-700';
                $bulletClass = 'bg-blue-500';
            } elseif (in_array($statusStr, ['đã giao thành công', 'completed', 'delivered', 'hoàn thành'])) {
                $statusText = 'Đã giao thành công';
                $statusClass = 'bg-emerald-50 text-emerald-700';
                $bulletClass = 'bg-emerald-500';
            } elseif (in_array($statusStr, ['đã hủy', 'cancelled', 'canceled'])) {
                $statusText = 'Đã hủy';
                $statusClass = 'bg-red-50 text-red-700';
                $bulletClass = 'bg-red-500';
            }

            return [
                'id' => $order->id,
                'code' => '#SS-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'customerName' => $order->name,
                'customerEmail' => $order->email,
                'date' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i'),
                'total' => (float)$order->total_amount,
                'statusText' => $statusText,
                'statusClass' => $statusClass,
                'bulletClass' => $bulletClass
            ];
        });

        // CHART DATA
        $now = Carbon::now();
        // 1. Week
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $weekData = Order::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->whereNotIn('status', ['cancelled', 'đã hủy', 'huy'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('revenue', 'date');

        $chartWeek = [];
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        foreach ($days as $index => $day) {
            $date = $startOfWeek->copy()->addDays($index)->format('Y-m-d');
            $chartWeek[] = [
                'label' => $day,
                'value' => $weekData->has($date) ? (float)$weekData[$date] : 0
            ];
        }

        // 2. Month
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $monthData = Order::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['cancelled', 'đã hủy', 'huy'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('revenue', 'date');
            
        $chartMonth = [
            ['label' => 'Tuần 1', 'value' => 0],
            ['label' => 'Tuần 2', 'value' => 0],
            ['label' => 'Tuần 3', 'value' => 0],
            ['label' => 'Tuần 4', 'value' => 0]
        ];
        foreach ($monthData as $date => $revenue) {
            $day = Carbon::parse($date)->day;
            if ($day <= 7) $chartMonth[0]['value'] += (float)$revenue;
            elseif ($day <= 14) $chartMonth[1]['value'] += (float)$revenue;
            elseif ($day <= 21) $chartMonth[2]['value'] += (float)$revenue;
            else $chartMonth[3]['value'] += (float)$revenue;
        }

        // 3. Year
        $yearData = Order::select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_amount) as revenue'))
            ->whereYear('created_at', $now->year)
            ->whereNotIn('status', ['cancelled', 'đã hủy', 'huy'])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('revenue', 'month');

        $chartYear = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartYear[] = [
                'label' => 'T' . $i,
                'value' => $yearData->has($i) ? (float)$yearData[$i] : 0
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => $revenue,
                'orders' => $ordersCount,
                'customers' => $customersCount,
                'totalProducts' => $productsCount,
                'bestSellers' => $bestSellers,
                'recentOrders' => $recentOrders,
                'chartData' => [
                    'week' => $chartWeek,
                    'month' => $chartMonth,
                    'year' => $chartYear
                ]
            ]
        ]);
    });

    // Quản lý Tin tức (Blogs)
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

    // Quản lý Banners (Admin)
    Route::get('/banners', [BannerController::class, 'adminIndex']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::post('/banners/{id}', [BannerController::class, 'update']);
    Route::patch('/banners/{id}/toggle', [BannerController::class, 'toggleActive']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
});


