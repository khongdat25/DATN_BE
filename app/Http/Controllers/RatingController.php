<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Gửi đánh giá cho một sản phẩm trong đơn hàng đã giao
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'order_item_id' => 'required|integer',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
        ]);

        $orderItem = OrderItem::with(['order', 'variant'])->find($request->order_item_id, ['*']);

        if (! $orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm trong đơn hàng không tồn tại',
            ], 404);
        }

        // Kiểm tra quyền sở hữu đơn hàng của người dùng
        if ($orderItem->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền đánh giá đơn hàng này',
            ], 403);
        }

        // Kiểm tra trạng thái đơn hàng (Phải ở trạng thái đã giao hàng 'delivered')
        if ($orderItem->order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể đánh giá sản phẩm khi đơn hàng đã giao thành công',
            ], 400);
        }

        // Kiểm tra xem đã đánh giá sản phẩm này trong đơn hàng này chưa
        $existingRating = rating::where('order_item_id', '=', $orderItem->id, 'and')->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đánh giá sản phẩm này trong đơn hàng rồi',
            ], 400);
        }

        $productId = $orderItem->variant->product_id ?? null;

        if (! $productId) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm tương ứng',
            ], 400);
        }

        $ratingObj = rating::create([
            'user_id'       => $user->id,
            'product_id'    => $productId,
            'order_item_id' => $orderItem->id,
            'rating'        => $request->rating,
            'comment'       => $request->comment ?? '',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gửi đánh giá sản phẩm thành công!',
            'data'    => $ratingObj,
        ], 201);
    }
}
