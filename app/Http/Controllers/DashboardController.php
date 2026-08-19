<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard-stats",
     *     summary="[Admin] Lấy dữ liệu thống kê tổng quan (Dashboard)",
     *     description="Thống kê tổng doanh thu, số đơn hàng hoàn thành, khách hàng, sản phẩm, top bán chạy và biểu đồ doanh thu",
     *     tags={"Dashboard Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function stats(Request $request)
    {
        $completedPayments = ['paid', 'đã thanh toán', 'thành công', 1];
        $completedStatuses = ['delivered', 'completed', 'đã giao hàng', 'đã giao thành công', 'hoàn thành'];

        // 1. Tổng doanh thu (Chỉ cộng tiền của các đơn hàng đã thanh toán VÀ giao hàng hoàn thành)
        $revenue = Order::query()
            ->whereIn('payment_status', $completedPayments, 'and', false)
            ->whereIn('status', $completedStatuses, 'and', false)
            ->sum('total_amount');

        // 2. Tổng số đơn hàng đã hoàn thành (Đã thanh toán + Đã giao hàng)
        $ordersCount = Order::query()
            ->whereIn('payment_status', $completedPayments, 'and', false)
            ->whereIn('status', $completedStatuses, 'and', false)
            ->count('*');

        // 3. Số lượng tài khoản khách hàng
        $customersCount = User::query()->where('role', '=', 'user', 'and')->count('*');

        // 4. Tổng số lượng sản phẩm trong kho
        $productsCount = Product::query()->count('*');

        // 5. Top sản phẩm bán chạy (Chỉ thống kê từ các đơn hàng đã thanh toán & hoàn thành)
        $bestSellersRaw = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_item.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->whereIn('orders.payment_status', $completedPayments, 'and', false)
            ->whereIn('orders.status', $completedStatuses, 'and', false)
            ->select([
                'products.id',
                'products.name',
                'products.images',
                DB::raw('SUM(order_item.quantity) as sales'),
                DB::raw('SUM(order_item.quantity * order_item.price) as revenue')
            ])
            ->groupBy('products.id', 'products.name', 'products.images')
            ->orderByDesc('sales')
            ->take(3)
            ->get();

        $bestSellers = $bestSellersRaw->map(function ($item) {
            $images = json_decode($item->images, true);
            $image = (is_array($images) && count($images) > 0) ? $images[0] : '/images/placeholder.png';
            if (! str_starts_with($image, 'http') && ! str_starts_with($image, '/')) {
                $image = '/'.$image;
            }
            if (! str_starts_with($image, 'http') && ! str_starts_with($image, '/images') && ! str_starts_with($image, 'images')) {
                $image = '/images/'.ltrim($image, '/');
            }

            return [
                'id' => $item->id,
                'name' => $item->name,
                'sales' => (int) $item->sales,
                'revenue' => (float) $item->revenue,
                'image' => url($image),
                'trendingUp' => true,
                'change' => rand(5, 20).'%',
            ];
        });

        // 6. Danh sách 5 đơn hàng mới nhất
        $recentOrders = Order::orderBy('created_at', 'desc')->take(5)->get()->map(function ($order) use ($completedPayments, $completedStatuses) {
            $statusStr = strtolower((string) $order->status);
            $paymentStatusStr = strtolower((string) $order->payment_status);

            $isCompletedStatus = in_array($statusStr, $completedStatuses);
            $isPaidStatus = in_array($paymentStatusStr, ['paid', 'đã thanh toán', 'thành công', '1']);

            if ($isCompletedStatus && $isPaidStatus) {
                $statusText = 'Hoàn thành';
                $statusClass = 'bg-emerald-50 text-emerald-700';
                $bulletClass = 'bg-emerald-500';
            } elseif (in_array($statusStr, ['đang giao', 'shipping', 'shipped'])) {
                $statusText = 'Đang giao hàng';
                $statusClass = 'bg-blue-50 text-blue-700';
                $bulletClass = 'bg-blue-500';
            } elseif (in_array($statusStr, ['đã hủy', 'cancelled', 'canceled'])) {
                $statusText = 'Đã hủy';
                $statusClass = 'bg-red-50 text-red-700';
                $bulletClass = 'bg-red-500';
            } else {
                $statusText = 'Chờ xử lý';
                $statusClass = 'bg-amber-50 text-amber-700';
                $bulletClass = 'bg-amber-500';
            }

            return [
                'id' => $order->id,
                'code' => '#SS-'.str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'customerName' => $order->name,
                'customerEmail' => $order->email,
                'date' => Carbon::parse($order->created_at)->format('d/m/Y H:i'),
                'total' => (float) $order->total_amount,
                'statusText' => $statusText,
                'statusClass' => $statusClass,
                'bulletClass' => $bulletClass,
            ];
        });

        // 7. Biểu đồ doanh thu (Chỉ tính doanh thu các đơn hàng hoàn thành)
        $now = Carbon::now();
        // Biểu đồ Tuần
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $weekData = Order::query()
            ->select([DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue')])
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->whereIn('payment_status', $completedPayments, 'and', false)
            ->whereIn('status', $completedStatuses, 'and', false)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('revenue', 'date');

        $chartWeek = [];
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        foreach ($days as $index => $day) {
            $date = $startOfWeek->copy()->addDays($index)->format('Y-m-d');
            $chartWeek[] = [
                'label' => $day,
                'value' => $weekData->has($date) ? (float) $weekData[$date] : 0,
            ];
        }

        // Biểu đồ Tháng
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $monthData = Order::query()
            ->select([DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue')])
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereIn('payment_status', $completedPayments, 'and', false)
            ->whereIn('status', $completedStatuses, 'and', false)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('revenue', 'date');

        $chartMonth = [
            ['label' => 'Tuần 1', 'value' => 0],
            ['label' => 'Tuần 2', 'value' => 0],
            ['label' => 'Tuần 3', 'value' => 0],
            ['label' => 'Tuần 4', 'value' => 0],
        ];
        foreach ($monthData as $date => $rev) {
            $day = Carbon::parse($date)->day;
            if ($day <= 7) {
                $chartMonth[0]['value'] += (float) $rev;
            } elseif ($day <= 14) {
                $chartMonth[1]['value'] += (float) $rev;
            } elseif ($day <= 21) {
                $chartMonth[2]['value'] += (float) $rev;
            } else {
                $chartMonth[3]['value'] += (float) $rev;
            }
        }

        // Biểu đồ Năm
        $yearData = Order::query()
            ->select([DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_amount) as revenue')])
            ->whereYear('created_at', $now->year)
            ->whereIn('payment_status', $completedPayments, 'and', false)
            ->whereIn('status', $completedStatuses, 'and', false)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('revenue', 'month');

        $chartYear = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartYear[] = [
                'label' => 'T'.$i,
                'value' => $yearData->has($i) ? (float) $yearData[$i] : 0,
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
                    'year' => $chartYear,
                ],
            ],
        ]);
    }
}
