<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create payment_methods table
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });

            // Seed default payment methods
            DB::table('payment_methods')->insert([
                [
                    'id' => 1,
                    'name' => 'Tiền mặt khi nhận hàng (COD)',
                    'code' => 'cod',
                    'description' => 'Thanh toán trực tiếp cho shipper khi nhận hàng',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 2,
                    'name' => 'Chuyển khoản ngân hàng (VietQR)',
                    'code' => 'qr_bank',
                    'description' => 'Chuyển khoản nhanh qua mã VietQR',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 3,
                    'name' => 'Cổng thanh toán VNPAY',
                    'code' => 'vnpay',
                    'description' => 'Thanh toán trực tuyến qua VNPAY',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 2. Modify orders.payment_method_id type and add foreign key
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->change();
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('payment_method_id')
                    ->references('id')
                    ->on('payment_methods')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['payment_method_id']);
            });
        }

        Schema::dropIfExists('payment_methods');
    }
};
