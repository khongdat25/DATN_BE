<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'address', 'note',
        'total_amount', 'voucher_id', 'payment_method_id', 'payment_status', 'status',
        'cancel_reason', 'bank_name', 'bank_account_number', 'bank_account_name', 'refund_notes',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function histories()
    {
        return $this->hasMany(OrderHistory::class, 'order_id')->orderBy('created_at', 'desc');
    }

    protected static function booted()
    {
        static::updating(function ($order) {
            $isStatusChanged = $order->isDirty('status');
            $isPaymentStatusChanged = $order->isDirty('payment_status');

            if ($isStatusChanged || $isPaymentStatusChanged) {
                $userId = auth()->id() ?: null;
                $userName = auth()->check() ? auth()->user()->name : null;

                $noteParts = [];
                if ($isStatusChanged) {
                    $noteParts[] = 'Cập nhật trạng thái';
                }
                if ($isPaymentStatusChanged) {
                    $noteParts[] = 'Cập nhật thanh toán';
                }
                $noteText = implode(' & ', $noteParts);
                if ($userName) {
                    $noteText .= " bởi {$userName}";
                } else {
                    $noteText .= ' bởi hệ thống';
                }

                \App\Models\OrderHistory::create([
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'old_status' => $order->getOriginal('status'),
                    'new_status' => $order->status,
                    'old_payment_status' => $order->getOriginal('payment_status'),
                    'new_payment_status' => $order->payment_status,
                    'note' => $noteText,
                ]);
            }
        });

        static::created(function ($order) {
            \App\Models\OrderHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id() ?: ($order->user_id ?: null),
                'old_status' => null,
                'new_status' => $order->status,
                'old_payment_status' => null,
                'new_payment_status' => $order->payment_status,
                'note' => 'Khởi tạo đơn hàng',
            ]);
        });
    }
}
