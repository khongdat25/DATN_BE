<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayOS\PayOS;
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
        ]);

        $isBuyNow = isset($data['variant_id']);

        if ($isBuyNow) {
            $variant = Variant::find($data['variant_id'], ['*']);
            if (!$variant) {
                return response()->json(['message' => 'Variant not found'], 404);
            }
            $cartItems = collect([
                (object)[
                    'variant_id' => $variant->id,
                    'quantity' => $data['quantity'] ?? 1,
                    'variant' => $variant
                ]
            ]);
        } else {
            $cartItems = Cart::query()->with('variant')->where(['user_id' => $user->id])->get();
            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Cart is empty'], 400);
            }
        }

        $total = 0;
        foreach ($cartItems as $item) {
            $price = $item->variant->price ?? 0;
            $total += $price * $item->quantity;
        }

        $shippingFee = $data['shipping_fee'] ?? 0;

        // Apply voucher discount if any
        if (!empty($data['voucher_id'])) {
            $voucher = \App\Models\Voucher::find($data['voucher_id']);
            if ($voucher && $voucher->status === 'active' && \Carbon\Carbon::now()->startOfDay()->lte(\Carbon\Carbon::parse($voucher->end_date))) {
                if ($total >= $voucher->min_order) {
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
                }
            }
        }

        $total += $shippingFee;

        DB::beginTransaction();
        try {
            $clientId = env('PAYOS_CLIENT_ID');
            $apiKey = env('PAYOS_API_KEY');
            $checksumKey = env('PAYOS_CHECKSUM_KEY');

            $isQrPayment = ($data['payment_method_id'] == 2 || $data['payment_method_id'] == 3);

            // Set initial order status: 'pending' (Chờ xác nhận) if QR + PayOS enabled, else 'new' (Đang chờ duyệt)
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
                'status' => ($isQrPayment && $clientId) ? 'pending' : 'new',
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

            // Check if PayOS API is reachable (to prevent timeouts if server is offline or cURL fails)
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

            // Create PayOS payment link if keys are configured and reachable
            $payOSResponse = null;
            if ($isQrPayment && $clientId && $apiKey && $checksumKey && $isPayOsReachable) {
                try {
                    $payOS = new PayOS($clientId, $apiKey, $checksumKey);
                    $baseUrl = env('FRONTEND_URL', 'http://localhost:5173');

                    $paymentData = [
                        'orderCode' => $order->id,
                        'amount' => (int)$total,
                        'description' => 'SGS-' . $order->id,
                        'cancelUrl' => $baseUrl . '/checkout?status=cancelled&order_id=' . $order->id,
                        'returnUrl' => $baseUrl . '/profile?tab=orders&status=success&order_id=' . $order->id,
                    ];

                    $payOSResponse = $payOS->createPaymentLink($paymentData);
                } catch (\Exception $payOSError) {
                    \Illuminate\Support\Facades\Log::warning('PayOS Link Creation Failed: ' . $payOSError->getMessage());
                    // Fallback to active order status
                    $order->status = 'new';
                    $order->save();
                }
            } elseif ($isQrPayment) {
                // If QR payment but PayOS not reachable, mark order as new (COD-like behavior)
                $order->status = 'new';
                $order->save();
            }

            // clear cart only if this is NOT a buy-now checkout
            if (!$isBuyNow) {
                Cart::query()->where(['user_id' => $user->id])->delete();
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
     * Webhook xử lý phản hồi thanh toán thành công từ PayOS
     */
    public function payosWebhook(Request $request)
    {
        $clientId = env('PAYOS_CLIENT_ID');
        $apiKey = env('PAYOS_API_KEY');
        $checksumKey = env('PAYOS_CHECKSUM_KEY');

        if (!$clientId || !$apiKey || !$checksumKey) {
            return response()->json(['success' => false, 'message' => 'PayOS credentials not configured'], 500);
        }

        $payOS = new PayOS($clientId, $apiKey, $checksumKey);

        try {
            // Verify webhook signature and retrieve transaction data
            $webhookData = $payOS->verifyPaymentWebhookData($request->all());

            $orderId = $webhookData['orderCode'];
            $order = Order::find($orderId);

            if ($order && $order->status === 'pending') {
                $order->status = 'new'; // 'new' is 'Đang chờ duyệt'
                $order->payment_status = 'paid';
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
