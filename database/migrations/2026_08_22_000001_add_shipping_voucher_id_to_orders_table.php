<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_voucher_id')) {
                $table->unsignedBigInteger('shipping_voucher_id')->nullable()->after('voucher_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_voucher_id')) {
                $table->dropColumn('shipping_voucher_id');
            }
        });
    }
};
