<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'note' => 'sometimes|string',
            'payment_method_id' => 'sometimes|integer',
            'voucher_id' => 'sometimes|integer',
        ]);

        $cartItems = Cart::query()->with('variant')->where(['user_id' => $user->id])->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $total = 0;
        foreach ($cartItems as $item) {
            $price = $item->variant->price ?? 0;
            $total += $price * $item->quantity;
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'address' => $data['address'] ?? null,
                'note' => $data['note'] ?? null,
                'total_amount' => $total,
                'voucher_id' => $data['voucher_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'payment_status' => 'pending',
                'status' => 'new',
            ]);

            foreach ($cartItems as $item) {
                $variant = Variant::find($item->variant_id, ['*']);
                $price = $variant->price ?? 0;

                OrderItem::create([
                    'order_id' => $order->id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price' => $price,
                ]);

                // decrement stock if available
                if ($variant && isset($variant->stock)) {
                    $variant->stock = max(0, $variant->stock - $item->quantity);
                    $variant->save();
                }
            }

            // clear cart
            Cart::query()->where(['user_id' => $user->id])->delete();

            DB::commit();
            return response()->json(['data' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }
}
