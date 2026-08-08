<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'ghn_order_code')) {
                $table->string('ghn_order_code')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'province_id')) {
                $table->integer('province_id')->nullable()->after('address');
            }
            if (!Schema::hasColumn('orders', 'district_id')) {
                $table->integer('district_id')->nullable()->after('province_id');
            }
            if (!Schema::hasColumn('orders', 'ward_code')) {
                $table->string('ward_code')->nullable()->after('district_id');
            }
            if (!Schema::hasColumn('orders', 'shipping_fee')) {
                $table->decimal('shipping_fee', 12, 2)->default(0)->after('total_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['ghn_order_code', 'province_id', 'district_id', 'ward_code', 'shipping_fee']);
        });
    }
};
