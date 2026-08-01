<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/ratings",
     *     summary="Gửi đánh giá sản phẩm",
     *     tags={"Đánh giá sản phẩm (Rating)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_item_id","rating"},
     *             @OA\Property(property="order_item_id", type="integer", example=1),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Giày đi rất êm chân, giao hàng nhanh!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Gửi đánh giá thành công")
     * )
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

        if ($orderItem->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền đánh giá đơn hàng này',
            ], 403);
        }

        if ($orderItem->order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể đánh giá sản phẩm khi đơn hàng đã giao thành công',
            ], 400);
        }

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

    /**
     * @OA\Get(
     *     path="/api/admin/reviews",
     *     summary="[Admin] Danh sách đánh giá của khách hàng",
     *     tags={"Đánh giá sản phẩm (Rating)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="rating", in="query", required=false, description="Số sao (1-5 hoặc all)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái (pending/replied/hidden/all)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="q", in="query", required=false, description="Tìm theo tên/email/nội dung", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function adminIndex(Request $request)
    {
        $query = rating::query()
            ->with([
                'user:id,name,email,avatar',
                'product:id,name,images,category_id',
                'product.category:id,name',
                'orderItem:id,variant_id',
                'orderItem.variant:id,size_id,color_id',
                'orderItem.variant.size:id,name',
                'orderItem.variant.color:id,name',
            ])
            ->orderBy('id', 'desc');

        if ($request->filled('rating') && $request->rating !== 'all') {
            $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $data = $query->get()->map(function ($r) {
            $customerName = $r->user ? $r->user->name : 'Khách hàng';
            $customerEmail = $r->user ? $r->user->email : 'N/A';
            $customerAvatar = $r->user ? $r->user->avatar : null;

            $productName = $r->product ? $r->product->name : 'Sản phẩm';
            $productCategory = ($r->product && $r->product->category) ? $r->product->category->name : 'Thương hiệu';
            $productImages = $r->product ? $r->product->images : [];
            $productImage = is_array($productImages) && count($productImages) > 0 ? $productImages[0] : '';

            $sizeName = ($r->orderItem && $r->orderItem->variant && $r->orderItem->variant->size) ? $r->orderItem->variant->size->name : '';
            $colorName = ($r->orderItem && $r->orderItem->variant && $r->orderItem->variant->color) ? $r->orderItem->variant->color->name : '';

            $detailsStr = [];
            if ($sizeName) $detailsStr[] = "Size: {$sizeName}";
            if ($colorName) $detailsStr[] = "Màu: {$colorName}";
            $purchaseDetails = count($detailsStr) > 0 ? implode(' | ', $detailsStr) : 'Đã mua hàng thực tế';

            return [
                'id' => $r->id,
                'customer' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'avatar' => $customerAvatar,
                ],
                'product' => [
                    'name' => $productName,
                    'image' => $productImage,
                    'category' => $productCategory,
                ],
                'rating' => (int) $r->rating,
                'purchaseDetails' => $purchaseDetails,
                'date' => $r->created_at ? $r->created_at->format('d/m/Y H:i') : '',
                'comment' => $r->comment ?: 'Không có nhận xét văn bản.',
                'status' => $r->status ?: 'pending',
                'reply' => $r->reply,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách đánh giá thành công',
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/reviews/{id}/reply",
     *     summary="[Admin] Phản hồi đánh giá",
     *     tags={"Đánh giá sản phẩm (Rating)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reply"},
     *             @OA\Property(property="reply", type="string", example="Cảm ơn bạn đã tin tưởng SaigonShoes!")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Phản hồi thành công")
     * )
     */
    public function adminReply(Request $request, int $id)
    {
        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $ratingObj = rating::find($id, ['*']);
        if (! $ratingObj) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đánh giá'], 404);
        }

        $ratingObj->update([
            'reply'  => $request->reply,
            'status' => 'replied',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi phản hồi thành công!',
            'data'    => $ratingObj,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/reviews/{id}/status",
     *     summary="[Admin] Cập nhật trạng thái hiển thị/ẩn đánh giá",
     *     tags={"Đánh giá sản phẩm (Rating)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending","replied","hidden"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function adminUpdateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,replied,hidden',
        ]);

        $ratingObj = rating::find($id, ['*']);
        if (! $ratingObj) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đánh giá'], 404);
        }

        $ratingObj->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'data'    => $ratingObj,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/reviews/{id}",
     *     summary="[Admin] Xóa đánh giá",
     *     tags={"Đánh giá sản phẩm (Rating)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function adminDestroy(int $id)
    {
        $ratingObj = rating::find($id, ['*']);
        if (! $ratingObj) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đánh giá'], 404);
        }

        $ratingObj->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa đánh giá thành công',
        ], 200);
    }
}
