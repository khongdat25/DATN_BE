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
            if (! Schema::hasColumn('addresses', 'province_id')) {
                $table->integer('province_id')->nullable()->after('address');
            }
            if (! Schema::hasColumn('addresses', 'district_id')) {
                $table->integer('district_id')->nullable()->after('province_id');
            }
            if (! Schema::hasColumn('addresses', 'ward_code')) {
                $table->string('ward_code')->nullable()->after('district_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'province_id')) {
                $table->dropColumn('province_id');
            }
            if (Schema::hasColumn('addresses', 'district_id')) {
                $table->dropColumn('district_id');
            }
            if (Schema::hasColumn('addresses', 'ward_code')) {
                $table->dropColumn('ward_code');
            }
        });
    }
};
