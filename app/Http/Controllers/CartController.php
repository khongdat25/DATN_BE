<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Variant;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = Cart::query()->with([
            'variant:id,product_id,price,sale,color_id,size_id,image',
            'variant.product:id,name,slug,images,brand_id',
            'variant.product.brand:id,name',
            'variant.color:id,name',
            'variant.size:id,name'
        ])->where(['user_id' => $user->id])->get();

        // Ẩn các appended attributes nặng của ProductModel để tránh lỗi 500
        $items->each(function ($item) {
            if ($item->variant && $item->variant->product) {
                $item->variant->product->makeHidden(['avg_rating', 'min_price', 'image_urls']);
            }
        });

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'variant_id' => 'required|integer',
            'quantity' => 'sometimes|integer|min:1'
        ]);

        $variant = Variant::find($data['variant_id'], ['*']);
        if (!$variant) {
            return response()->json(['message' => 'Variant not found'], 404);
        }

        $quantity = $data['quantity'] ?? 1;

        $cart = Cart::query()->where(['user_id' => $user->id, 'variant_id' => $variant->id])->first();
        $currentQuantity = $cart ? $cart->quantity : 0;
        $newQuantity = $currentQuantity + $quantity;

        if (isset($variant->stock) && $newQuantity > $variant->stock) {
            return response()->json([
                'message' => 'Số lượng yêu cầu (' . $newQuantity . ') vượt quá tồn kho (Hiện có: ' . $variant->stock . ')'
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

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $data = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        // Try to find cart by cart.id first, then fall back to variant_id
        $cart = Cart::query()->where(['id' => $id, 'user_id' => $user->id])->first();
        if (! $cart) {
            $cart = Cart::query()->where(['variant_id' => $id, 'user_id' => $user->id])->first();
        }
        if (!$cart) return response()->json(['message' => 'Not found'], 404);

        $variant = Variant::find($cart->variant_id, ['*']);
        if ($variant && isset($variant->stock) && $data['quantity'] > $variant->stock) {
            return response()->json([
                'message' => 'Số lượng yêu cầu (' . $data['quantity'] . ') vượt quá tồn kho (Hiện có: ' . $variant->stock . ')'
            ], 400);
        }

        $cart->quantity = $data['quantity'];
        $cart->save();

        return response()->json(['data' => $cart]);
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $cart = Cart::query()->where(['id' => $id, 'user_id' => $user->id])->first();
        if (!$cart) return response()->json(['message' => 'Not found'], 404);

        $cart->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        Cart::query()->where(['user_id' => $user->id])->delete();
        return response()->json(['message' => 'Cart cleared']);
    }
}
