<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/orders",
     *     summary="[Admin] Danh sách tất cả đơn hàng",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
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
            'histories.user:id,name',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/orders/{id}/status",
     *     summary="[Admin] Cập nhật trạng thái đơn hàng",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"new","pending","shipping","delivered","cancelled"}),
     *             @OA\Property(property="payment_status", type="string", enum={"pending","paid","refunded"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function adminUpdateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:new,pending,shipping,delivered,cancelled',
            'payment_status' => 'sometimes|string|in:pending,paid,refunded',
        ]);

        $order = Order::findOrFail($id);

        $oldStatus = $order->status;

        if ($oldStatus !== $request->status) {
            $allowedTransitions = [
                'new' => ['pending', 'shipping', 'delivered', 'cancelled'],
                'pending' => ['shipping', 'delivered', 'cancelled'],
                'shipping' => ['delivered', 'cancelled'],
                'delivered' => [],
                'cancelled' => [],
            ];

            $statusLabels = [
                'new' => 'Mới',
                'pending' => 'Chờ xử lý',
                'shipping' => 'Đang giao hàng',
                'delivered' => 'Đã giao hàng',
                'cancelled' => 'Đã hủy',
            ];

            if (! isset($allowedTransitions[$oldStatus]) || ! in_array($request->status, $allowedTransitions[$oldStatus])) {
                $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
                $newLabel = $statusLabels[$request->status] ?? $request->status;

                return response()->json([
                    'success' => false,
                    'message' => "Không thể chuyển đổi trạng thái đơn hàng từ '{$oldLabel}' sang '{$newLabel}'",
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

            // Send order status update notification to customer
            if ($order->user_id) {
                $statusTitles = [
                    'pending' => 'Đơn hàng #SGS-' . $order->id . ' đang được chuẩn bị 📦',
                    'shipping' => 'Đơn hàng #SGS-' . $order->id . ' đang được vận chuyển! 🚚',
                    'delivered' => 'Đơn hàng #SGS-' . $order->id . ' đã giao thành công! 🎉',
                    'cancelled' => 'Đơn hàng #SGS-' . $order->id . ' đã bị hủy ❌',
                ];
                $statusBodies = [
                    'pending' => 'Cửa hàng đã xác nhận và đang đóng gói sản phẩm của bạn.',
                    'shipping' => 'Đơn vị vận chuyển đã tiếp nhận và đang trên đường giao đến địa chỉ của bạn.',
                    'delivered' => 'Đơn hàng đã được giao thành công. Đừng quên đánh giá sản phẩm nhé!',
                    'cancelled' => 'Rất tiếc, đơn hàng #SGS-' . $order->id . ' đã bị hủy.',
                ];

                if (isset($statusTitles[$request->status])) {
                    try {
                        \App\Http\Controllers\NotificationController::sendToUser(
                            $order->user_id,
                            $statusTitles[$request->status],
                            $statusBodies[$request->status],
                            'order',
                            '/profile'
                        );
                    } catch (\Exception $e) {
                        // Ignore notification error
                    }
                }
            }

            if ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
                if ($order->voucher_id) {
                    $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                    if ($voucher) {
                        $voucher->decrement('used_count');
                    }
                }

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
                'data' => $order,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Cập nhật trạng thái đơn hàng thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/orders/{id}",
     *     summary="[Admin] Xóa đơn hàng",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function adminDestroy(int $id)
    {
        $order = Order::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($order->status !== 'cancelled' && $order->voucher_id) {
                $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                if ($voucher) {
                    $voucher->decrement('used_count');
                }
            }

            $order->items()->delete();
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa đơn hàng thành công',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Xóa đơn hàng thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user/orders",
     *     summary="Danh sách đơn mua của tôi",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function userIndex(Request $request)
    {
        $user = $request->user();
        $orders = Order::with([
            'items.rating',
            'items.variant' => function ($query) {
                $query->withTrashed();
            },
            'items.variant.product' => function ($query) {
                $query->withTrashed()->select(['id', 'name', 'images']);
            },
            'items.variant.size:id,name',
            'items.variant.color:id,name',
            'histories.user:id,name',
        ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/user/orders/{id}/cancel",
     *     summary="Hủy đơn hàng của tôi",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", example="Đổi ý không muốn mua nữa"),
     *             @OA\Property(property="restore_cart", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Hủy thành công")
     * )
     */
    public function userCancel(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::query()->where('id', '=', $id, 'and')->where('user_id', '=', $user->id, 'and')->firstOrFail();

        if (! in_array($order->status, ['new', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng ở trạng thái này',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';

            if (!empty($order->ghn_order_code)) {
                try {
                    $ghnController = new \App\Http\Controllers\GHNController();
                    $ghnController->cancelGHNOrder($order->id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Auto cancel GHN for user order exception: ' . $e->getMessage());
                }
            }

            if ($request->filled('reason')) {
                $order->cancel_reason = $request->input('reason');
            } elseif ($request->filled('cancel_reason')) {
                $order->cancel_reason = $request->input('cancel_reason');
            }

            if ($request->filled('bank_name')) {
                $order->bank_name = $request->input('bank_name');
            }
            if ($request->filled('bank_account_number')) {
                $order->bank_account_number = $request->input('bank_account_number');
            }
            if ($request->filled('bank_account_name')) {
                $order->bank_account_name = $request->input('bank_account_name');
            }
            if ($request->filled('refund_notes')) {
                $order->refund_notes = $request->input('refund_notes');
            }

            $order->save();

            if ($order->voucher_id) {
                $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                if ($voucher) {
                    $voucher->decrement('used_count');
                }
            }

            $orderItems = OrderItem::query()->where('order_id', '=', $order->id, 'and')->get();
            foreach ($orderItems as $item) {
                $variant = Variant::find($item->variant_id, ['*']);
                if ($variant && isset($variant->stock)) {
                    $variant->stock += $item->quantity;
                    $variant->save();
                }
            }

            if ($request->boolean('restore_cart')) {
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
                            'quantity' => $item->quantity,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hủy đơn hàng thành công',
                'data' => $order,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Hủy đơn hàng thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/user/orders/{id}/confirm-payment",
     *     summary="Xác nhận thanh toán đơn hàng",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xác nhận thành công")
     * )
     */
    public function userConfirmPayment(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::query()->where('id', '=', $id, 'and')->where('user_id', '=', $user->id, 'and')->firstOrFail();

        if ($order->status === 'pending') {
            $order->status = 'new';
            $order->payment_status = 'paid';
            $order->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Xác nhận thanh toán thành công',
            'data' => $order,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/user/orders/{id}",
     *     summary="Chi tiết một đơn hàng của tôi",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
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
            'items.variant.color:id,name',
            'histories.user:id,name',
        ])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $order,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/user/orders/{id}",
     *     summary="Hủy và khôi phục giỏ hàng cho đơn chưa thanh toán",
     *     tags={"Đơn hàng (Order)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function userDestroy(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::query()->where('id', '=', $id, 'and')->where('user_id', '=', $user->id, 'and')->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng đã duyệt hoặc đã thanh toán',
            ], 400);
        }

        DB::beginTransaction();
        try {
            if ($order->voucher_id) {
                $voucher = \App\Models\Voucher::find($order->voucher_id, ['*']);
                if ($voucher) {
                    $voucher->decrement('used_count');
                }
            }

            $orderItems = OrderItem::query()->where('order_id', '=', $order->id, 'and')->get();

            foreach ($orderItems as $item) {
                $variant = Variant::find($item->variant_id, ['*']);
                if ($variant && isset($variant->stock)) {
                    $variant->stock += $item->quantity;
                    $variant->save();
                }
            }

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
                        'quantity' => $item->quantity,
                    ]);
                }
            }

            $order->items()->delete();
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy bỏ đơn hàng và khôi phục giỏ hàng thành công',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Hủy đơn hàng thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
