<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    // === API Dành Cho Quản Trị Viên (Admin) ===

    /**
     * Lấy danh sách tất cả mã giảm giá
     * 
     * @OA\Get(
     *     path="/api/admin/vouchers",
     *     summary="[Admin] Danh sách tất cả mã giảm giá (Vouchers)",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index()
    {
        $vouchers = Voucher::orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $vouchers], 200);
    }

    /**
     * Tạo mới mã giảm giá (Admin)
     * 
     * @OA\Post(
     *     path="/api/admin/vouchers",
     *     summary="[Admin] Tạo mới mã giảm giá (Voucher)",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","name","type","value","min_order","total_usage","start_date","end_date","status"},
     *             @OA\Property(property="code", type="string", example="SGS100K"),
     *             @OA\Property(property="name", type="string", example="Giảm 100k cho đơn từ 1 triệu"),
     *             @OA\Property(property="type", type="string", enum={"percent","fixed","free_ship"}, example="fixed"),
     *             @OA\Property(property="value", type="number", example=100000),
     *             @OA\Property(property="min_order", type="number", example=1000000),
     *             @OA\Property(property="total_usage", type="integer", example=50),
     *             @OA\Property(property="max_discount", type="number", example=null),
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-08-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-08-31"),
     *             @OA\Property(property="status", type="string", enum={"active","upcoming","expired"}, example="active")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
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

    /**
     * Xem chi tiết thông tin mã giảm giá (Admin)
     * 
     * @OA\Get(
     *     path="/api/admin/vouchers/{id}",
     *     summary="[Admin] Chi tiết mã giảm giá",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function show(int $id)
    {
        $voucher = Voucher::find($id, ['*']);
        if (! $voucher) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mã giảm giá'], 404);
        }

        return response()->json(['success' => true, 'data' => $voucher], 200);
    }

    /**
     * Cập nhật thông tin mã giảm giá (Admin)
     * 
     * @OA\Put(
     *     path="/api/admin/vouchers/{id}",
     *     summary="[Admin] Cập nhật mã giảm giá",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string", enum={"percent","fixed","free_ship"}),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="min_order", type="number"),
     *             @OA\Property(property="total_usage", type="integer"),
     *             @OA\Property(property="max_discount", type="number"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"active","upcoming","expired"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, int $id)
    {
        $voucher = Voucher::find($id, ['*']);
        if (! $voucher) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mã giảm giá'], 404);
        }

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

    /**
     * Xóa mã giảm giá (Admin)
     * 
     * @OA\Delete(
     *     path="/api/admin/vouchers/{id}",
     *     summary="[Admin] Xóa mã giảm giá",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(int $id)
    {
        $voucher = Voucher::find($id, ['*']);
        if (! $voucher) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mã giảm giá'], 404);
        }
        $voucher->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa mã giảm giá thành công'], 200);
    }

    // === API Dành Cho Khách Hàng (User) ===

    /**
     * Lấy danh sách các mã giảm giá khả dụng cho tài khoản người dùng
     * 
     * @OA\Get(
     *     path="/api/vouchers/available",
     *     summary="Danh sách mã giảm giá khả dụng cho tôi",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
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
                return ! in_array($voucher->id, $usedVoucherIds);
            })->values();
        }

        return response()->json(['success' => true, 'data' => $vouchers], 200);
    }

    /**
     * Kiểm tra và áp dụng mã giảm giá vào đơn hàng khi checkout
     * 
     * @OA\Post(
     *     path="/api/vouchers/apply",
     *     summary="Áp dụng mã giảm giá khi thanh toán",
     *     tags={"Quản lý Mã giảm giá (Voucher)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","subtotal"},
     *             @OA\Property(property="code", type="string", example="SGS100K"),
     *             @OA\Property(property="subtotal", type="number", example=1200000),
     *             @OA\Property(property="shipping_fee", type="number", example=30000)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Áp dụng thành công"),
     *     @OA\Response(response=400, description="Mã không hợp lệ hoặc không đủ điều kiện")
     * )
     */
    public function applyVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric',
            'shipping_fee' => 'sometimes|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', '=', $request->code, 'and')->first();

        if (! $voucher) {
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
            'message' => 'Áp dụng mã thành công',
        ], 200);
    }
}
