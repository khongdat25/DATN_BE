<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Lấy dữ liệu thống kê cho trang Dashboard của Admin.
     */
    public function stats(Request $request)
    {
        $revenue = Order::query()->whereNotIn('status', ['cancelled', 'đã hủy', 'huy'], 'and')->sum('total_amount');
        $ordersCount = Order::query()->count('*');
        $customersCount = User::query()->where('role', '=', 'user', 'and')->count('*');
        $productsCount = ProductModel::query()->count('*');

        // Best sellers
        $bestSellersRaw = DB::table('order_item')
            ->join('product_variants', 'order_item.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
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

        // Recent orders
        $recentOrders = Order::orderBy('created_at', 'desc')->take(5)->get()->map(function ($order) {
            $statusStr = strtolower((string) $order->status);
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

        // CHART DATA
        $now = Carbon::now();
        // 1. Week
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $weekData = Order::query()->select([DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue')])
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
                'value' => $weekData->has($date) ? (float) $weekData[$date] : 0,
            ];
        }

        // 2. Month
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $monthData = Order::query()->select([DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue')])
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['cancelled', 'đã hủy', 'huy'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('revenue', 'date');

        $chartMonth = [
            ['label' => 'Tuần 1', 'value' => 0],
            ['label' => 'Tuần 2', 'value' => 0],
            ['label' => 'Tuần 3', 'value' => 0],
            ['label' => 'Tuần 4', 'value' => 0],
        ];
        foreach ($monthData as $date => $revenue) {
            $day = Carbon::parse($date)->day;
            if ($day <= 7) {
                $chartMonth[0]['value'] += (float) $revenue;
            } elseif ($day <= 14) {
                $chartMonth[1]['value'] += (float) $revenue;
            } elseif ($day <= 21) {
                $chartMonth[2]['value'] += (float) $revenue;
            } else {
                $chartMonth[3]['value'] += (float) $revenue;
            }
        }

        // 3. Year
        $yearData = Order::query()->select([DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_amount) as revenue')])
            ->whereYear('created_at', $now->year)
            ->whereNotIn('status', ['cancelled', 'đã hủy', 'huy'])
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
