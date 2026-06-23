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
        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'name')) {
                $table->string('name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('addresses', 'badge')) {
                $table->string('badge')->default('Nhà riêng')->after('address');
            }
            if (!Schema::hasColumn('addresses', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('addresses', 'badge')) {
                $table->dropColumn('badge');
            }
            if (Schema::hasColumn('addresses', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
