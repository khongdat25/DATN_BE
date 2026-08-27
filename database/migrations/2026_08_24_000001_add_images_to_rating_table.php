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
        if (Schema::hasTable('rating') && !Schema::hasColumn('rating', 'images')) {
            Schema::table('rating', function (Blueprint $table) {
                $table->json('images')->nullable()->after('comment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rating') && Schema::hasColumn('rating', 'images')) {
            Schema::table('rating', function (Blueprint $table) {
                $table->dropColumn('images');
            });
        }
    }
};
