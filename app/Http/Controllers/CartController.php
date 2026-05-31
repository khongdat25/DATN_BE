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
        $items = Cart::with('variant')->where('user_id', $user->id)->get();
        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'variant_id' => 'required|integer',
            'quantity' => 'sometimes|integer|min:1'
        ]);

        $variant = Variant::find($data['variant_id']);
        if (!$variant) {
            return response()->json(['message' => 'Variant not found'], 404);
        }

        $quantity = $data['quantity'] ?? 1;

        $cart = Cart::where('user_id', $user->id)->where('variant_id', $variant->id)->first();
        if ($cart) {
            $cart->quantity += $quantity;
            $cart->save();
        } else {
            $cart = Cart::create([
                'user_id' => $user->id,
                'variant_id' => $variant->id,
                'quantity' => $quantity,
            ]);
        }

        return response()->json(['data' => $cart], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $data = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        // Try to find cart by cart.id first, then fall back to variant_id
        $cart = Cart::where('id', $id)->where('user_id', $user->id)->first();
        if (! $cart) {
            $cart = Cart::where('variant_id', $id)->where('user_id', $user->id)->first();
        }
        if (!$cart) return response()->json(['message' => 'Not found'], 404);

        $cart->quantity = $data['quantity'];
        $cart->save();

        return response()->json(['data' => $cart]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $cart = Cart::where('id', $id)->where('user_id', $user->id)->first();
        if (!$cart) return response()->json(['message' => 'Not found'], 404);

        $cart->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
