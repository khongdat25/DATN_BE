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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('note');
            }
            if (!Schema::hasColumn('orders', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('cancel_reason');
            }
            if (!Schema::hasColumn('orders', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('orders', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('orders', 'refund_notes')) {
                $table->text('refund_notes')->nullable()->after('bank_account_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_reason',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'refund_notes',
            ]);
        });
    }
};
