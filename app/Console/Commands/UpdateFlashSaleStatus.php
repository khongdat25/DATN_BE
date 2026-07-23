<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FlashSale;

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

        // Chờ -> Đang diễn ra
        FlashSale::where('status', 1)
            ->where('start_time', '<=', $now)
            ->update([
                'status' => 2
            ]);

        // Đang diễn ra -> Đã kết thúc
        FlashSale::where('status', 2)
            ->where('end_time', '<=', $now)
            ->update([
                'status' => 3
            ]);

        return Command::SUCCESS;
    }
}
