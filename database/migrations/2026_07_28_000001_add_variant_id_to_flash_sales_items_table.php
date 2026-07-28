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
        Schema::table('flash_sales_items', function (Blueprint $table) {
            if (!Schema::hasColumn('flash_sales_items', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_sales_items', function (Blueprint $table) {
            if (Schema::hasColumn('flash_sales_items', 'variant_id')) {
                $table->dropColumn('variant_id');
            }
        });
    }
};
