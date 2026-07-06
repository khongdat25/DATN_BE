<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoucherController extends Controller
{
    // === API for Admin ===

    public function index()
    {
        $vouchers = Voucher::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $vouchers], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code',
            'name' => 'required|string',
            'type' => 'required|in:percent,fixed,free_ship',
            'value' => 'required|numeric|min:0',
            'min_order' => 'required|numeric|min:0',
            'total_usage' => 'required|integer|min:1',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,upcoming,expired',
        ]);

        $voucher = Voucher::create($validated);
        return response()->json(['success' => true, 'data' => $voucher], 201);
    }

    public function show(int $id)
    {
        $voucher = Voucher::find($id, ['*']);
        if (!$voucher) return response()->json(['success' => false, 'message' => 'Voucher not found'], 404);
        return response()->json(['success' => true, 'data' => $voucher], 200);
    }

    public function update(Request $request, int $id)
    {
        $voucher = Voucher::find($id, ['*']);
        if (!$voucher) return response()->json(['success' => false, 'message' => 'Voucher not found'], 404);

        $validated = $request->validate([
            'code' => 'sometimes|string|unique:vouchers,code,'.$id,
            'name' => 'sometimes|string',
            'type' => 'sometimes|in:percent,fixed,free_ship',
            'value' => 'sometimes|numeric|min:0',
            'min_order' => 'sometimes|numeric|min:0',
            'total_usage' => 'sometimes|integer|min:1',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,upcoming,expired',
        ]);

        $voucher->update($validated);
        return response()->json(['success' => true, 'data' => $voucher], 200);
    }

    public function destroy(int $id)
    {
        $voucher = Voucher::find($id, ['*']);
        if (!$voucher) return response()->json(['success' => false, 'message' => 'Voucher not found'], 404);
        $voucher->delete();
        return response()->json(['success' => true, 'message' => 'Voucher deleted'], 200);
    }

    // === API for User Checkout ===

    public function getAvailableVouchers(Request $request)
    {
        $user = $request->user();

        $vouchers = Voucher::where('status', '=', 'active', 'and')
            ->whereDate('start_date', '<=', Carbon::now())
            ->whereDate('end_date', '>=', Carbon::now())
            ->whereColumn('used_count', '<', 'total_usage')
            ->get();

        if ($user) {
            $usedVoucherIds = \App\Models\Order::where('user_id', '=', $user->id, 'and')
                ->whereNotNull('voucher_id')
                ->where('status', '!=', 'cancelled', 'and')
                ->pluck('voucher_id')
                ->toArray();

            $vouchers = $vouchers->filter(function ($voucher) use ($usedVoucherIds) {
                return !in_array($voucher->id, $usedVoucherIds);
            })->values();
        }

        return response()->json(['success' => true, 'data' => $vouchers], 200);
    }

    public function applyVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric',
            'shipping_fee' => 'sometimes|numeric|min:0'
        ]);

        $voucher = Voucher::where('code', '=', $request->code, 'and')->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá không tồn tại'], 404);
        }

        if ($voucher->status !== 'active' || Carbon::now()->startOfDay()->gt(Carbon::parse($voucher->end_date))) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết hạn hoặc bị khóa'], 400);
        }

        if (Carbon::now()->startOfDay()->lt(Carbon::parse($voucher->start_date))) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá chưa đến thời gian bắt đầu'], 400);
        }

        if ($voucher->used_count >= $voucher->total_usage) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng'], 400);
        }

        // Check if user has already used this voucher
        $user = $request->user();
        if ($user) {
            $alreadyUsed = \App\Models\Order::where('user_id', '=', $user->id, 'and')
                ->where('voucher_id', '=', $voucher->id, 'and')
                ->where('status', '!=', 'cancelled', 'and')
                ->exists();

            if ($alreadyUsed) {
                return response()->json(['success' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này rồi'], 400);
            }
        }

        if ($request->subtotal < $voucher->min_order) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã này'], 400);
        }

        $shippingFee = $request->shipping_fee ?? 0;

        // Calculate discount
        $discount = 0;
        if ($voucher->type === 'percent') {
            $discount = ($request->subtotal * $voucher->value) / 100;
            if ($voucher->max_discount && $discount > $voucher->max_discount) {
                $discount = $voucher->max_discount;
            }
        } elseif ($voucher->type === 'fixed') {
            $discount = $voucher->value;
        } elseif ($voucher->type === 'free_ship') {
            if ($shippingFee == 0) {
                return response()->json(['success' => false, 'message' => 'Đơn hàng của bạn đã được miễn phí vận chuyển sẵn rồi!'], 400);
            }
            $discount = min($shippingFee, $voucher->value);
        }

        return response()->json([
            'success' => true, 
            'data' => $voucher, 
            'discount' => $discount,
            'message' => 'Áp dụng mã thành công'
        ], 200);
    }
}
