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
        if (Schema::hasTable('brands')) {
            Schema::table('brands', function (Blueprint $table) {
                if (!Schema::hasColumn('brands', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
        if (Schema::hasTable('colors')) {
            Schema::table('colors', function (Blueprint $table) {
                if (!Schema::hasColumn('colors', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
        if (Schema::hasTable('sizes')) {
            Schema::table('sizes', function (Blueprint $table) {
                if (!Schema::hasColumn('sizes', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('brands')) {
            Schema::table('brands', function (Blueprint $table) {
                if (Schema::hasColumn('brands', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
        if (Schema::hasTable('colors')) {
            Schema::table('colors', function (Blueprint $table) {
                if (Schema::hasColumn('colors', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
        if (Schema::hasTable('sizes')) {
            Schema::table('sizes', function (Blueprint $table) {
                if (Schema::hasColumn('sizes', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
