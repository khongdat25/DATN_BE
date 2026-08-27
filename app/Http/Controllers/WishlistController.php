<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get user wishlist (favorite products)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['product' => function ($q) {
                $q->where('status', 1)->with(['brand', 'category', 'variants']);
            }])
            ->get()
            ->pluck('product')
            ->filter();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $favorites->values()
        ]);
    }

    /**
     * Toggle a product in favorite/wishlist
     */
    public function toggle(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $productId = $request->input('product_id');
        if (!$productId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product ID is required'
            ], 400);
        }

        $existing = Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $wished = false;
            $message = 'Đã xóa sản phẩm khỏi danh sách yêu thích';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $wished = true;
            $message = 'Đã thêm sản phẩm vào danh sách yêu thích ❤️';
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'wished' => $wished,
            'message' => $message
        ]);
    }

    /**
     * Remove specific product from favorite/wishlist
     */
    public function destroy(int $productId, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi danh sách yêu thích'
        ]);
    }

    /**
     * Clear all favorite/wishlist items for logged-in user
     */
    public function clear(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        Favorite::where('user_id', $user->id)->delete();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Đã xóa tất cả sản phẩm khỏi danh sách yêu thích'
        ]);
    }
}
