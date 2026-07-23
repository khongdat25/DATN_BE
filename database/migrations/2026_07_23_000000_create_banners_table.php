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
                $table->string('name');
                $table->string('image');
                $table->string('link')->nullable();
                $table->text('description')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('banners', function (Blueprint $table) {
                if (!Schema::hasColumn('banners', 'link')) {
                    $table->string('link')->nullable();
                }
                if (!Schema::hasColumn('banners', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('banners', 'position')) {
                    $table->integer('position')->default(0);
                }
                if (!Schema::hasColumn('banners', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('banners', 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn('banners', 'deleted_at')) {
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
        // Keep existing table or drop columns
    }
};
