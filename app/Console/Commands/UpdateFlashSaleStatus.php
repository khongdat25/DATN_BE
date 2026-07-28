<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Flashsale;

class UpdateFlashSaleStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashsale:update-status';

    protected $description = 'Update flash sale status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        // Tự động kết thúc chiến dịch nếu đã quá giờ end_time
        Flashsale::query()
            ->where('status', '!=', 3)
            ->where('end_time', '<=', $now)
            ->update([
                'status' => 3
            ]);

        return self::SUCCESS;
    }
}
