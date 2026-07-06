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
        // 1. Modify orders.voucher_id column type to bigint unsigned (so it matches vouchers.id type)
        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('voucher_id')->unsigned()->nullable()->change();
        });

        // 2. Add foreign key constraints
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('comment', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('favorite', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('set null');
        });

        Schema::table('order_item', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('set null');
        });

        Schema::table('rating', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('order_item')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rating', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['order_item_id']);
        });

        Schema::table('order_item', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['variant_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['voucher_id']);
        });

        Schema::table('favorite', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('comment', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Restore orders.voucher_id to int signed and nullable
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('voucher_id')->nullable()->change();
        });
    }
};
