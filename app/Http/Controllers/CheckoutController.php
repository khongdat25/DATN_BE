<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PayOS\PayOS;

class CheckoutController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/checkout",
     *     summary="Tạo đơn hàng & Thanh toán (Checkout)",
     *     description="Tạo đơn hàng từ giỏ hàng hoặc mua ngay, tích hợp cổng thanh toán PayOS nếu chọn thanh toán QR",
     *     tags={"Thanh toán (Checkout)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","phone","address"},
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="email", type="string", example="nguyenvana@gmail.com"),
     *             @OA\Property(property="phone", type="string", example="0987654321"),
     *             @OA\Property(property="address", type="string", example="123 Nguyễn Huệ, Q.1, TP.HCM"),
     *             @OA\Property(property="note", type="string", example="Giao buổi chiều"),
     *             @OA\Property(property="payment_method_id", type="integer", example=1, description="1: COD, 2: PayOS QR"),
     *             @OA\Property(property="voucher_id", type="integer", example=null),
     *             @OA\Property(property="shipping_fee", type="number", example=30000),
     *             @OA\Property(property="variant_id", type="integer", example=null, description="Nếu Mua Ngay"),
     *             @OA\Property(property="quantity", type="integer", example=null, description="Số lượng Mua Ngay"),
     *             @OA\Property(property="cart_item_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo đơn hàng thành công", @OA\JsonContent(@OA\Property(property="data", type="object"), @OA\Property(property="checkout_url", type="string"))),
     *     @OA\Response(response=400, description="Lỗi dữ liệu / Tồn kho không đủ")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'sometimes|nullable|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'note' => 'sometimes|nullable|string',
            'payment_method_id' => 'sometimes|nullable|integer',
            'voucher_id' => 'sometimes|nullable|integer',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1',
            'cart_item_ids' => 'sometimes|nullable|array',
            'cart_item_ids.*' => 'integer',
        ]);

        $isBuyNow = isset($data['variant_id']);

        if ($isBuyNow) {
            $variant = Variant::find($data['variant_id'], ['*']);
            if (! $variant) {
                return response()->json(['message' => 'Variant not found'], 404);
            }
            $cartItems = collect([
                (object) [
                    'variant_id' => $variant->id,
                    'quantity' => $data['quantity'] ?? 1,
                    'variant' => $variant,
                ],
            ]);
        } else {
            $query = Cart::query()->with('variant')->where(['user_id' => $user->id]);
            if (! empty($data['cart_item_ids'])) {
                $query->whereIn('id', $data['cart_item_ids']);
            }
            $cartItems = $query->get();
            if ($cartItems->isEmpty()) {
                \Illuminate\Support\Facades\Log::warning('Checkout failed: Cart is empty for user '.$user->id);

                return response()->json(['message' => 'Cart is empty'], 400);
            }
        }

        $shippingFee = $data['shipping_fee'] ?? 0;

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($cartItems as $item) {
                $price = $item->variant->price ?? 0;
                $total += $price * $item->quantity;
            }

            $appliedVoucherId = null;

            if (! empty($data['voucher_id'])) {
                $voucher = \App\Models\Voucher::where('id', '=', $data['voucher_id'], 'and')
                    ->lockForUpdate()
                    ->first();

                if (! $voucher) {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Voucher not found for user '.$user->id);

                    return response()->json(['message' => 'Mã giảm giá không tồn tại'], 400);
                }

                $now = \Carbon\Carbon::now();

                if ($voucher->status !== 'active') {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Voucher inactive for user '.$user->id);

                    return response()->json(['message' => 'Mã giảm giá đã bị khóa hoặc không hoạt động'], 400);
                }

                if ($voucher->start_date && $now->startOfDay()->lt(\Carbon\Carbon::parse($voucher->start_date))) {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Voucher not started for user '.$user->id);

                    return response()->json(['message' => 'Mã giảm giá chưa đến thời gian bắt đầu'], 400);
                }

                if ($voucher->end_date && $now->startOfDay()->gt(\Carbon\Carbon::parse($voucher->end_date))) {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Voucher expired for user '.$user->id);

                    return response()->json(['message' => 'Mã giảm giá đã hết hạn hoặc bị khóa'], 400);
                }

                if ($voucher->used_count >= $voucher->total_usage) {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Voucher fully used for user '.$user->id);

                    return response()->json(['message' => 'Mã giảm giá đã hết lượt sử dụng'], 400);
                }

                $alreadyUsed = Order::where('user_id', '=', $user->id, 'and')
                    ->where('voucher_id', '=', $voucher->id, 'and')
                    ->where('status', '!=', 'cancelled', 'and')
                    ->exists();

                if ($alreadyUsed) {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Voucher already used by user '.$user->id);

                    return response()->json(['message' => 'Bạn đã sử dụng mã giảm giá này rồi'], 400);
                }

                if ($total < $voucher->min_order) {
                    DB::rollBack();
                    \Illuminate\Support\Facades\Log::warning('Checkout failed: Order total less than min_order for user '.$user->id);

                    return response()->json(['message' => 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã này'], 400);
                }

                $discount = 0;
                if ($voucher->type === 'percent') {
                    $discount = ($total * $voucher->value) / 100;
                    if ($voucher->max_discount && $discount > $voucher->max_discount) {
                        $discount = $voucher->max_discount;
                    }
                } elseif ($voucher->type === 'fixed') {
                    $discount = $voucher->value;
                } elseif ($voucher->type === 'free_ship') {
                    $discount = min($shippingFee, $voucher->value);
                }

                $total = max(0, $total - $discount);
                $voucher->increment('used_count');
                $appliedVoucherId = $voucher->id;
            }

            $total += $shippingFee;

            $clientId = env('PAYOS_CLIENT_ID');
            $apiKey = env('PAYOS_API_KEY');
            $checksumKey = env('PAYOS_CHECKSUM_KEY');

            $isQrPayment = ($data['payment_method_id'] == 2 || $data['payment_method_id'] == 3);

            $order = Order::create([
                'user_id' => $user->id,
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'address' => $data['address'] ?? null,
                'province_id' => $request->input('province_id'),
                'district_id' => $request->input('district_id'),
                'ward_code' => $request->input('ward_code'),
                'shipping_fee' => $shippingFee,
                'note' => $data['note'] ?? null,
                'total_amount' => $total,
                'voucher_id' => $appliedVoucherId,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'payment_status' => 'pending',
                'status' => ($isQrPayment && $clientId) ? 'pending' : 'new',
            ]);

            foreach ($cartItems as $item) {
                $variant = Variant::where('id', '=', $item->variant_id, 'and')
                    ->lockForUpdate()
                    ->first();

                if (! $variant) {
                    DB::rollBack();

                    return response()->json(['message' => 'Sản phẩm hoặc biến thể không tồn tại'], 404);
                }

                if (isset($variant->stock) && $variant->stock < $item->quantity) {
                    DB::rollBack();
                    $productName = $variant->product->name ?? 'Sản phẩm';
                    $sizeName = $variant->size->name ?? $variant->size_id;
                    $colorName = $variant->color->name ?? $variant->color_id;

                    return response()->json([
                        'message' => "Sản phẩm {$productName} (Size {$sizeName} - Màu {$colorName}) không đủ số lượng trong kho (Hiện còn {$variant->stock})",
                    ], 400);
                }

                $price = $variant->price ?? 0;

                OrderItem::create([
                    'order_id' => $order->id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price' => $price,
                ]);

                if (isset($variant->stock)) {
                    $variant->stock = max(0, $variant->stock - $item->quantity);
                    $variant->save();
                }
            }

            $isPayOsReachable = false;
            if ($isQrPayment && $clientId && $apiKey && $checksumKey) {
                $connection = @fsockopen('api-merchant.payos.vn', 443, $errno, $errstr, 1.5);
                if ($connection) {
                    $isPayOsReachable = true;
                    fclose($connection);
                } else {
                    \Illuminate\Support\Facades\Log::warning("PayOS API not reachable: $errstr ($errno). Falling back to standard checkout.");
                }
            }

            $payOSResponse = null;
            if ($isQrPayment && $clientId && $apiKey && $checksumKey && $isPayOsReachable) {
                try {
                    $payOS = new PayOS($clientId, $apiKey, $checksumKey);
                    $baseUrl = env('FRONTEND_URL', 'http://localhost:5173');

                    $paymentData = [
                        'orderCode' => $order->id,
                        'amount' => (int) $total,
                        'description' => 'SGS-'.$order->id,
                        'cancelUrl' => $baseUrl.'/checkout?payment_cancel=1&order_id='.$order->id,
                        'returnUrl' => $baseUrl.'/profile?tab=orders&payment_success=1&order_id='.$order->id,
                    ];

                    $payOSResponse = $payOS->createPaymentLink($paymentData);
                } catch (\Exception $payOSError) {
                    \Illuminate\Support\Facades\Log::warning('PayOS Link Creation Failed: '.$payOSError->getMessage());
                    $order->status = 'new';
                    $order->save();
                }
            } elseif ($isQrPayment) {
                $order->status = 'new';
                $order->save();
            }

            if (! $isBuyNow) {
                $deleteQuery = Cart::query()->where(['user_id' => $user->id]);
                if (! empty($data['cart_item_ids'])) {
                    $deleteQuery->whereIn('id', $data['cart_item_ids']);
                }
                $deleteQuery->delete();
            }

            DB::commit();

            $responsePayload = ['data' => $order];
            if ($payOSResponse) {
                $responsePayload['checkout_url'] = $payOSResponse['checkoutUrl'] ?? '';
                $responsePayload['qr_code'] = $payOSResponse['qrCode'] ?? '';
            }

            return response()->json($responsePayload, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment/payos-webhook",
     *     summary="Webhook xử lý kết quả thanh toán từ PayOS",
     *     tags={"Thanh toán (Checkout)"},
     *     @OA\Response(response=200, description="Đã xử lý webhook")
     * )
     */
    public function payosWebhook(Request $request)
    {
        $clientId = env('PAYOS_CLIENT_ID');
        $apiKey = env('PAYOS_API_KEY');
        $checksumKey = env('PAYOS_CHECKSUM_KEY');

        if (! $clientId || ! $apiKey || ! $checksumKey) {
            return response()->json(['success' => false, 'message' => 'PayOS credentials not configured'], 500);
        }

        $payOS = new PayOS($clientId, $apiKey, $checksumKey);

        try {
            // Verify webhook signature and retrieve transaction data
            $webhookData = $payOS->verifyPaymentWebhookData($request->all());

            $orderId = $webhookData['orderCode'];
            $order = Order::find($orderId, ['*']);

            if ($order && $order->status === 'pending') {
                $order->status = 'new'; // 'new' is 'Đang chờ duyệt'
                $order->payment_status = 'paid';
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: '.$e->getMessage(),
            ], 400);
        }
    }
}
