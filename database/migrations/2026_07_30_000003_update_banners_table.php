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
        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('image')->nullable();
                $table->string('link')->nullable();
                $table->string('position')->default('Trang chủ - Slider chính (Hero)');
                $table->string('status')->default('active');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('banners', function (Blueprint $table) {
                if (!Schema::hasColumn('banners', 'title')) {
                    $table->string('title')->nullable()->after('id');
                }
                if (!Schema::hasColumn('banners', 'link')) {
                    $table->string('link')->nullable()->after('image');
                }
                if (!Schema::hasColumn('banners', 'position')) {
                    $table->string('position')->default('Trang chủ - Slider chính (Hero)')->after('link');
                }
                if (!Schema::hasColumn('banners', 'status')) {
                    $table->string('status')->default('active')->after('position');
                }
                if (!Schema::hasColumn('banners', 'start_date')) {
                    $table->date('start_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('banners', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('banners', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
