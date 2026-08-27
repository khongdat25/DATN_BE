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
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'flash_sale_id')) {
                $table->integer('flash_sale_id')->nullable()->after('color_id');
            }
            if (!Schema::hasColumn('product_variants', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable()->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'flash_sale_id')) {
                $table->dropColumn('flash_sale_id');
            }
            if (Schema::hasColumn('product_variants', 'sale_price')) {
                $table->dropColumn('sale_price');
            }
        });
    }
};
