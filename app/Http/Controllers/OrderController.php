<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use App\Models\Cart;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Lấy danh sách tất cả các đơn hàng (Quản trị viên)
     */
    public function adminIndex()
    {
        $orders = Order::with([
            'items.variant' => function ($query) {
                $query->withTrashed();
            },
            'items.variant.product' => function ($query) {
                $query->withTrashed()->select(['id', 'name', 'images']);
            },
            'items.variant.size:id,name',
            'items.variant.color:id,name',
            'histories.user:id,name'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }

    /**
     * Cập nhật trạng thái đơn hàng (Quản trị viên)
     */
    public function adminUpdateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:new,pending,shipping,delivered,cancelled',
            'payment_status' => 'sometimes|string|in:pending,paid,refunded'
        ]);

        $order = Order::findOrFail($id);
        
        $oldStatus = $order->status;

        // Tránh cho phép thay đổi ngược (lùi trạng thái) hoặc thay đổi khi đã ở trạng thái kết thúc
        if ($oldStatus !== $request->status) {
            $allowedTransitions = [
                'new'       => ['pending', 'shipping', 'delivered', 'cancelled'],
                'pending'   => ['shipping', 'delivered', 'cancelled'],
                'shipping'  => ['delivered', 'cancelled'],
                'delivered' => [], // Trạng thái kết thúc
                'cancelled' => [], // Trạng thái kết thúc
            ];

            $statusLabels = [
                'new'       => 'Mới',
                'pending'   => 'Chờ xử lý',
                'shipping'  => 'Đang giao hàng',
                'delivered' => 'Đã giao hàng',
                'cancelled' => 'Đã hủy',
            ];

            if (!isset($allowedTransitions[$oldStatus]) || !in_array($request->status, $allowedTransitions[$oldStatus])) {
                $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
                $newLabel = $statusLabels[$request->status] ?? $request->status;
                return response()->json([
                    'success' => false,
                    'message' => "Không thể chuyển đổi trạng thái đơn hàng từ '{$oldLabel}' sang '{$newLabel}'"
                ], 400);
            }
        }

        $order->status = $request->status;

        if ($request->filled('payment_status')) {
            $order->payment_status = $request->payment_status;
        }

        DB::beginTransaction();
        try {
            $order->save();

            // Nếu chuyển sang trạng thái cancelled và trước đó chưa cancelled
            if ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
                if ($order->voucher_id) {
                    $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                    if ($voucher) {
                        $voucher->decrement('used_count');
                    }
                }

                // Hoàn trả lại số lượng tồn kho (stock) cho các variant trong đơn hàng khi admin hủy đơn
                $orderItems = OrderItem::query()->where('order_id', '=', $order->id, 'and')->get();
                foreach ($orderItems as $item) {
                    $variant = Variant::find($item->variant_id, ['*']);
                    if ($variant && isset($variant->stock)) {
                        $variant->stock += $item->quantity;
                        $variant->save();
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái đơn hàng thành công',
                'data' => $order
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật trạng thái đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa đơn hàng (Quản trị viên)
     */
    public function adminDestroy(int $id)
    {
        $order = Order::findOrFail($id);

        DB::beginTransaction();
        try {
            // Khôi phục lượt dùng voucher nếu đơn hàng chưa bị cancelled trước đó
            if ($order->status !== 'cancelled' && $order->voucher_id) {
                $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                if ($voucher) {
                    $voucher->decrement('used_count');
                }
            }

            // Xóa tất cả các items thuộc đơn hàng trước
            $order->items()->delete();
            $order->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Xóa đơn hàng thành công'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Xóa đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách đơn mua của người dùng hiện tại
     */
    public function userIndex(Request $request)
    {
        $user = $request->user();
        $orders = Order::with([
            'items.variant' => function ($query) {
                $query->withTrashed();
            },
            'items.variant.product' => function ($query) {
                $query->withTrashed()->select(['id', 'name', 'images']);
            },
            'items.variant.size:id,name',
            'items.variant.color:id,name'
        ])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }

    /**
     * Hủy đơn hàng (Người dùng tự hủy)
     */
    public function userCancel(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::query()->where('id', '=', $id, 'and')->where('user_id', '=', $user->id, 'and')->firstOrFail();

        // Chỉ cho phép hủy khi đơn ở trạng thái mới hoặc chờ xử lý
        if (!in_array($order->status, ['new', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng ở trạng thái này'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';
            $order->save();

            // Khôi phục lượt dùng voucher
            if ($order->voucher_id) {
                $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                if ($voucher) {
                    $voucher->decrement('used_count');
                }
            }

            // Hoàn trả lại số lượng tồn kho (stock) cho các variant trong đơn hàng
            $orderItems = OrderItem::query()->where('order_id', '=', $order->id, 'and')->get();
            foreach ($orderItems as $item) {
                $variant = Variant::find($item->variant_id, ['*']);
                if ($variant && isset($variant->stock)) {
                    $variant->stock += $item->quantity;
                    $variant->save();
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Hủy đơn hàng thành công',
                'data' => $order
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Hủy đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một đơn hàng của người dùng hiện tại
     */
    public function userShow(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::with([
            'items.variant' => function ($query) {
                $query->withTrashed();
            },
            'items.variant.product' => function ($query) {
                $query->withTrashed()->select(['id', 'name', 'images']);
            },
            'items.variant.size:id,name',
            'items.variant.color:id,name'
        ])
        ->where('id', $id)
        ->where('user_id', $user->id)
        ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }

    /**
     * Xóa/Hủy vĩnh viễn đơn hàng chưa thanh toán và khôi phục giỏ hàng
     */
    public function userDestroy(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::query()->where('id', '=', $id, 'and')->where('user_id', '=', $user->id, 'and')->firstOrFail();

        // Chỉ cho phép xóa khi đơn hàng ở trạng thái 'pending'
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng đã duyệt hoặc đã thanh toán'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Khôi phục lượt dùng voucher
            if ($order->voucher_id) {
                $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                if ($voucher) {
                    $voucher->decrement('used_count');
                }
            }

            $orderItems = OrderItem::query()->where('order_id', '=', $order->id, 'and')->get();
            
            // 1. Hoàn trả lại số lượng tồn kho (stock) cho các variant
            foreach ($orderItems as $item) {
                $variant = Variant::find($item->variant_id, ['*']);
                if ($variant && isset($variant->stock)) {
                    $variant->stock += $item->quantity;
                    $variant->save();
                }
            }

            // 2. Khôi phục lại giỏ hàng cho user
            foreach ($orderItems as $item) {
                $existingCart = Cart::where('user_id', '=', $user->id, 'and')
                                    ->where('variant_id', '=', $item->variant_id, 'and')
                                    ->first();
                if ($existingCart) {
                    $existingCart->quantity += $item->quantity;
                    $existingCart->save();
                } else {
                    Cart::create([
                        'user_id' => $user->id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity
                    ]);
                }
            }

            // 3. Xóa items và xóa đơn hàng
            $order->items()->delete();
            $order->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Đã hủy bỏ đơn hàng và khôi phục giỏ hàng thành công'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Hủy đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
