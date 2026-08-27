<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Variant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/cart",
     *     summary="Xem sản phẩm trong giỏ hàng",
     *     tags={"Giỏ hàng (Cart)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $items = Cart::query()->with([
            'variant:id,product_id,price,sale_price,flash_sale_id,color_id,size_id,image',
            'variant.flashSale',
            'variant.product:id,name,slug,images,brand_id',
            'variant.product.brand:id,name',
            'variant.color:id,name',
            'variant.size:id,name',
        ])->where(['user_id' => $user->id])->get();

        $items->each(function ($item) {
            if ($item->variant && $item->variant->product) {
                $item->variant->product->makeHidden(['avg_rating', 'min_price', 'image_urls']);
            }
        });

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * @OA\Post(
     *     path="/api/cart",
     *     summary="Thêm sản phẩm vào giỏ hàng",
     *     tags={"Giỏ hàng (Cart)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"variant_id"},
     *             @OA\Property(property="variant_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Đã thêm vào giỏ hàng"),
     *     @OA\Response(response=400, description="Vượt quá tồn kho")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'variant_id' => 'required|integer',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $variant = Variant::find($data['variant_id'], ['*']);
        if (! $variant) {
            return response()->json(['message' => 'Variant not found'], 404);
        }

        $quantity = $data['quantity'] ?? 1;

        $cart = Cart::query()->where(['user_id' => $user->id, 'variant_id' => $variant->id])->first();
        $currentQuantity = $cart ? $cart->quantity : 0;
        $newQuantity = $currentQuantity + $quantity;

        if (isset($variant->stock) && $newQuantity > $variant->stock) {
            return response()->json([
                'message' => 'Số lượng yêu cầu ('.$newQuantity.') vượt quá tồn kho (Hiện có: '.$variant->stock.')',
            ], 400);
        }

        if ($cart) {
            $cart->quantity = $newQuantity;
            $cart->save();
        } else {
            $cart = Cart::create([
                'user_id' => $user->id,
                'variant_id' => $variant->id,
                'quantity' => $newQuantity,
            ]);
        }

        return response()->json(['data' => $cart], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/cart/{id}",
     *     summary="Cập nhật số lượng sản phẩm trong giỏ hàng",
     *     tags={"Giỏ hàng (Cart)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Cart ID hoặc Variant ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::query()->where(['id' => $id, 'user_id' => $user->id])->first();
        if (! $cart) {
            $cart = Cart::query()->where(['variant_id' => $id, 'user_id' => $user->id])->first();
        }
        if (! $cart) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $variant = Variant::find($cart->variant_id, ['*']);
        if ($variant && isset($variant->stock) && $data['quantity'] > $variant->stock) {
            return response()->json([
                'message' => 'Số lượng yêu cầu ('.$data['quantity'].') vượt quá tồn kho (Hiện có: '.$variant->stock.')',
            ], 400);
        }

        $cart->quantity = $data['quantity'];
        $cart->save();

        return response()->json(['data' => $cart]);
    }

    /**
     * @OA\Delete(
     *     path="/api/cart/{id}",
     *     summary="Xóa mục khỏi giỏ hàng",
     *     tags={"Giỏ hàng (Cart)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Cart ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $cart = Cart::query()->where(['id' => $id, 'user_id' => $user->id])->first();
        if (! $cart) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $cart->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * @OA\Delete(
     *     path="/api/cart/clear",
     *     summary="Xóa toàn bộ giỏ hàng",
     *     tags={"Giỏ hàng (Cart)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Đã xóa toàn bộ giỏ hàng")
     * )
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        Cart::query()->where(['user_id' => $user->id])->delete();

        return response()->json(['message' => 'Cart cleared']);
    }
}
