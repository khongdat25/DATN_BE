<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-expired-orders';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Tự động hủy các đơn hàng trực tuyến ở trạng thái pending quá 15 phút và khôi phục tồn kho, lượt dùng voucher';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu kiểm tra các đơn hàng quá hạn...');

        $expiredTime = now()->subMinutes(15);

        // Lấy tất cả các đơn hàng đang ở trạng thái pending (chờ thanh toán trực tuyến) quá 15 phút
        $expiredOrders = Order::where('status', '=', 'pending', 'and')
            ->where('created_at', '<', $expiredTime, 'and')
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Không tìm thấy đơn hàng nào quá hạn.');
            return;
        }

        $this->info('Tìm thấy ' . $expiredOrders->count() . ' đơn hàng quá hạn. Đang tiến hành hủy...');

        foreach ($expiredOrders as $order) {
            DB::beginTransaction();
            try {
                // 1. Hoàn trả lại số lượng tồn kho (stock) cho các variant
                $orderItems = OrderItem::where('order_id', '=', $order->id, 'and')->get();
                foreach ($orderItems as $item) {
                    $variant = Variant::find($item->variant_id, ['*']);
                    if ($variant && isset($variant->stock)) {
                        $variant->stock += $item->quantity;
                        $variant->save();
                    }
                }

                // 2. Khôi phục lượt dùng voucher
                if ($order->voucher_id) {
                    $voucher = Voucher::find($order->voucher_id, ['*']);
                    if ($voucher) {
                        $voucher->decrement('used_count');
                    }
                }

                // 3. Đổi trạng thái đơn hàng sang cancelled
                $order->status = 'cancelled';
                $order->save();

                DB::commit();

                $msg = "Đã tự động hủy thành công đơn hàng treo thanh toán ID: {$order->id}";
                $this->info($msg);
                Log::info($msg);

            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = "Lỗi khi tự động hủy đơn hàng ID {$order->id}: " . $e->getMessage();
                $this->error($errorMsg);
                Log::error($errorMsg);
            }
        }

        $this->info('Hoàn thành quá trình hủy đơn hàng quá hạn.');
    }
}
